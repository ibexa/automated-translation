<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation;

use Ibexa\AutomatedTranslation\Encoder\Field\FieldEncoderManager;
use Ibexa\AutomatedTranslation\Exception\EmptyTranslatedFieldException;
use Ibexa\Bundle\AutomatedTranslation\Event\FieldDecodeEvent;
use Ibexa\Bundle\AutomatedTranslation\Event\FieldEncodeEvent;
use Ibexa\Bundle\AutomatedTranslation\Events;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\Value;
use InvalidArgumentException;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class Encoder.
 *
 * Google Translate and Deepl (and probably others) are able to "ignore" markups when they translate
 *
 * This Encoder basically encodes a Field[] in XML of Translatable Fields (TextLineValue,TextBlockValue and
 * RichTextValue)
 *
 * Ex:
 *      <response>
 *             <title>The string value</title>
 *             <description>>The string value</description>
 *             <fieldIdentifier>value of the field converted into string</fieldIdentifier>
 *      </response>
 *
 * But as a RichTextValue is already a XML, the content Repository returns a valid XML already
 *         <?xml version="1.0" encoding="UTF-8"?><section><para>lorem ipsum</para></section>
 *
 * Then you end up with an XML Encoded like this (look for the <![CDATA[)
 *
 *      <response>
 *             <title>The string value</title>
 *             <description>
 *                  <![CDATA[<?xml version="1.0" encoding="UTF-8"?><section><para>lorem ipsum</para></section>]]>
 *              </description>
 *      </response>
 *
 * Which is bad because remote translation services are going to try to translate inside <![CDATA[ ]]>
 *
 * Then this Encoder fixes that, trusting the fact that RichTextValue is a valid XML
 *
 *      <response>
 *             <title>The string value</title>
 *             <description>
 *                  <fakecdata><section><para>lorem ipsum</para></section></fakecdata>
 *              </description>
 *      </response>
 *
 * Wrapping the valid XML in "<fakecdata>", the global XML is still valid, and the translation works
 *
 * The decode function reverses the wrapping.
 */
class Encoder
{
    /**
     * Use to fake the <![CDATA[ something ]]> to <fakecdata> something </fakecdata>.
     */
    private const CDATA_FAKER_TAG = 'fakecdata';

    private const XML_MARKUP = '<?xml version="1.0" encoding="UTF-8"?>';

    private ContentTypeService $contentTypeService;

    private EventDispatcherInterface $eventDispatcher;

    private FieldEncoderManager $fieldEncoderManager;

    public function __construct(
        ContentTypeService $contentTypeService,
        EventDispatcherInterface $eventDispatcher,
        FieldEncoderManager $fieldEncoderManager
    ) {
        $this->contentTypeService = $contentTypeService;
        $this->eventDispatcher = $eventDispatcher;
        $this->fieldEncoderManager = $fieldEncoderManager;
    }

    public function encode(Content $content): string
    {
        $results = [];
        $contentType = $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId);
        foreach ($content->getFields() as $field) {
            $identifier = $field->fieldDefIdentifier;
            $fieldDefinition = $contentType->getFieldDefinition($identifier);

            if (null === $fieldDefinition) {
                continue;
            }

            if (!$fieldDefinition->isTranslatable) {
                continue;
            }
            $type = \get_class($field->value);

            if (null === ($value = $this->encodeField($field))) {
                continue;
            }

            $results[$identifier] = [
                '#' => $value,
                '@type' => $type,
            ];
        }

        $encoder = new XmlEncoder();
        $payload = $encoder->encode($results, XmlEncoder::FORMAT);

        // here Encoder has  decorated with CDATA, we don't want the CDATA
        return str_replace(
            ['<![CDATA[', ']]>'],
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            $payload
        );
    }

    /**
     * @return array<int|string, \Ibexa\Core\FieldType\Value>
     */
    public function decode(string $xml, Content $sourceContent): array
    {
        $encoder = new XmlEncoder();
        $data = str_replace(
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            ['<![CDATA[' . self::XML_MARKUP, ']]>'],
            $xml
        );

        $decodeArray = $encoder->decode($data, XmlEncoder::FORMAT);
        $results = [];
        foreach ($decodeArray as $fieldIdentifier => $xmlValue) {
            $field = $sourceContent->getField($fieldIdentifier);

            if (null === $field) {
                continue;
            }

            $previousFieldValue = $field->value;
            $type = $xmlValue['@type'];
            $stringValue = $xmlValue['#'];

            if (null === ($fieldValue = $this->decodeField($type, $stringValue, $previousFieldValue))) {
                continue;
            }

            if (!in_array(SPIValue::class, (array) class_implements($type))) {
                throw new InvalidArgumentException(sprintf(
                    'Unable to instantiate class %s, it should implement %s',
                    $type,
                    SPIValue::class
                ));
            }

            if (!is_a($type, get_class($fieldValue), true)) {
                throw new InvalidArgumentException(sprintf(
                    'Decoded field class mismatch: expected %s, actual: %s',
                    $type,
                    get_class($fieldValue)
                ));
            }

            $results[$fieldIdentifier] = $fieldValue;
        }

        return $results;
    }

    private function encodeField(Field $field): ?string
    {
        try {
            $value = $this->fieldEncoderManager->encode($field);
        } catch (InvalidArgumentException $e) {
            return null;
        }

        $event = new FieldEncodeEvent($field, $value);
        $this->eventDispatcher->dispatch($event, Events::POST_FIELD_ENCODE);

        return $event->getValue();
    }

    /**
     * @param mixed $previousFieldValue
     */
    private function decodeField(string $type, string $value, $previousFieldValue): ?Value
    {
        try {
            $fieldValue = $this->fieldEncoderManager->decode($type, $value, $previousFieldValue);
        } catch (InvalidArgumentException | EmptyTranslatedFieldException $e) {
            return null;
        }

        $event = new FieldDecodeEvent($type, $fieldValue, $previousFieldValue);
        $this->eventDispatcher->dispatch($event, Events::POST_FIELD_DECODE);

        return $event->getValue();
    }
}
