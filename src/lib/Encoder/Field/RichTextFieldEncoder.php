<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Encoder\Field;

use Ibexa\AutomatedTranslation\Encoder\RichText\RichTextEncoder;
use Ibexa\AutomatedTranslation\Exception\EmptyTranslatedFieldException;
use Ibexa\Contracts\AutomatedTranslation\Encoder\Field\FieldEncoderInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\Value;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value as RichTextValue;

final class RichTextFieldEncoder implements FieldEncoderInterface
{
    private RichTextEncoder $richTextEncoder;

    public function __construct(
        RichTextEncoder $richTextEncoder
    ) {
        $this->richTextEncoder = $richTextEncoder;
    }

    public function canEncode(Field $field): bool
    {
        return $field->value instanceof RichTextValue;
    }

    public function canDecode(string $type): bool
    {
        return RichTextValue::class === $type;
    }

    public function encode(Field $field): string
    {
        return $this->richTextEncoder->encode((string) $field->value);
    }

    public function decode(string $value, $previousFieldValue): Value
    {
        $decodedValue = $this->richTextEncoder->decode($value);

        if (strlen($decodedValue) === 0) {
            throw new EmptyTranslatedFieldException();
        }

        return new RichTextValue($decodedValue);
    }
}
