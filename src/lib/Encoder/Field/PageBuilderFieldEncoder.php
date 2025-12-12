<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Encoder\Field;

use Ibexa\AutomatedTranslation\Encoder\BlockAttribute\BlockAttributeEncoderManager;
use Ibexa\AutomatedTranslation\EncoderHelper;
use Ibexa\AutomatedTranslation\Exception\EmptyTranslatedAttributeException;
use Ibexa\Contracts\AutomatedTranslation\Encoder\Field\FieldEncoderInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\Value as APIValue;
use Ibexa\FieldTypePage\FieldType\LandingPage\Value;
use Ibexa\FieldTypePage\FieldType\Page\Block\Definition\BlockDefinitionFactoryInterface;
use InvalidArgumentException;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

final class PageBuilderFieldEncoder implements FieldEncoderInterface
{
    private const CDATA_FAKER_TAG = 'fake_blocks_cdata';

    private BlockAttributeEncoderManager $blockAttributeEncoderManager;

    private BlockDefinitionFactoryInterface $blockDefinitionFactory;

    private EncoderHelper $encoderHelper;

    public function __construct(
        BlockAttributeEncoderManager $blockAttributeEncoderManager,
        BlockDefinitionFactoryInterface $blockDefinitionFactory,
        EncoderHelper $encoderHelper
    ) {
        $this->blockAttributeEncoderManager = $blockAttributeEncoderManager;
        $this->blockDefinitionFactory = $blockDefinitionFactory;
        $this->encoderHelper = $encoderHelper;
    }

    public function canEncode(Field $field): bool
    {
        return class_exists(Value::class) && $field->value instanceof Value;
    }

    public function canDecode(string $type): bool
    {
        return class_exists(Value::class) && is_a($type, Value::class, true);
    }

    public function encode(Field $field): string
    {
        /** @var \Ibexa\FieldTypePage\FieldType\LandingPage\Value $value */
        $value = $field->value;
        $page = $value->getPage();
        $blocks = [];

        $blockIterable = $page === null ? [] : $page->getBlockIterator();

        foreach ($blockIterable as $block) {
            $blockDefinition = $this->blockDefinitionFactory->getBlockDefinition($block->getType());
            $attrs = [];
            $attributes = $blockDefinition->getAttributes();

            foreach ($block->getAttributes() as $attribute) {
                $attributeName = $attribute->getName();
                if (empty($attributes[$attributeName])) {
                    continue;
                }
                $attributeType = $attributes[$attributeName]->getType();

                if (null === ($attributeValue = $this->encodeBlockAttribute($attributeType, $attribute->getValue()))) {
                    continue;
                }

                $attrs[$attributeName] = [
                    '@type' => $attributeType,
                    '#' => $attributeValue,
                ];
            }

            $blocks[$block->getId()] = [
              'name' => $block->getName(),
              'attributes' => $attrs,
            ];
        }

        $encoder = new XmlEncoder();
        $payload = $encoder->encode($blocks, XmlEncoder::FORMAT, [
            XmlEncoder::ROOT_NODE_NAME => 'blocks',
        ]);
        $payload = $this->encoderHelper->clearCDATAInTextField($payload);

        $payload = str_replace(
            ['<?xml version="1.0"?>' . "\n", '<![CDATA[', ']]>'],
            ['', '<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            $payload
        );

        return (string) $payload;
    }

    public function decode(string $value, $previousFieldValue): APIValue
    {
        $encoder = new XmlEncoder();
        $data = str_replace(
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            ['<![CDATA[', ']]>'],
            $value
        );

        /** @var \Ibexa\FieldTypePage\FieldType\LandingPage\Value $previousFieldValue */
        $page = $previousFieldValue->getPage();
        if ($page === null) {
            return new Value();
        }

        $page = clone $page;
        $decodeArray = $encoder->decode($data, XmlEncoder::FORMAT);

        if (!is_array($decodeArray)) {
            return new Value($page);
        }

        foreach ($decodeArray as $blockId => $xmlValue) {
            $block = $page->getBlockById((string) $blockId);
            $block->setName($xmlValue['name']);

            if (is_array($xmlValue['attributes'])) {
                foreach ($xmlValue['attributes'] as $attributeName => $attribute) {
                    if (null === ($attributeValue = $this->decodeBlockAttribute($attribute['@type'], $attribute['#']))) {
                        continue;
                    }

                    $block->getAttribute($attributeName)->setValue($attributeValue);
                }
            }
        }

        return new Value($page);
    }

    /**
     * @param mixed $value
     */
    private function encodeBlockAttribute(string $type, $value): ?string
    {
        try {
            $value = $this->blockAttributeEncoderManager->encode($type, $value);
        } catch (InvalidArgumentException $e) {
            return null;
        }

        return $value;
    }

    private function decodeBlockAttribute(string $type, string $value): ?string
    {
        try {
            $value = $this->blockAttributeEncoderManager->decode($type, $value);
        } catch (InvalidArgumentException | EmptyTranslatedAttributeException $e) {
            return null;
        }

        return $value;
    }
}

class_alias(PageBuilderFieldEncoder::class, 'EzSystems\EzPlatformAutomatedTranslation\Encoder\Field\PageBuilderFieldEncoder');
