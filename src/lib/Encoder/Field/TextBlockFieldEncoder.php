<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Encoder\Field;

use Ibexa\AutomatedTranslation\Exception\EmptyTranslatedFieldException;
use Ibexa\Contracts\AutomatedTranslation\Encoder\Field\FieldEncoderInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\TextBlock\Value as TextBlockValue;
use Ibexa\Core\FieldType\Value;

final class TextBlockFieldEncoder implements FieldEncoderInterface
{
    public function canEncode(Field $field): bool
    {
        return $field->value instanceof TextBlockValue;
    }

    public function canDecode(string $type): bool
    {
        return TextBlockValue::class === $type;
    }

    public function encode(Field $field): string
    {
        return (string) $field->value;
    }

    public function decode(string $value, $previousFieldValue): Value
    {
        $value = trim($value);

        if (strlen($value) === 0) {
            throw new EmptyTranslatedFieldException();
        }

        return new TextBlockValue($value);
    }
}
