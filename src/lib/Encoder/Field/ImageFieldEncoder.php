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
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Ibexa\Core\FieldType\Value;
use InvalidArgumentException;

/**
 * Translates the alternative text of an image, keeping the image itself untouched.
 */
final class ImageFieldEncoder implements FieldEncoderInterface
{
    public function canEncode(Field $field): bool
    {
        return $field->value instanceof ImageValue && !$field->value->isAlternativeTextEmpty();
    }

    public function canDecode(string $type): bool
    {
        return ImageValue::class === $type;
    }

    public function encode(Field $field): string
    {
        /** @var ImageValue $value */
        $value = $field->value;

        return (string) $value->alternativeText;
    }

    public function decode(
        string $value,
        $previousFieldValue
    ): Value {
        if (!$previousFieldValue instanceof ImageValue) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unable to decode image alternative text, expected previous field value of type %s.',
                    ImageValue::class
                )
            );
        }

        $value = trim($value);

        if (strlen($value) === 0) {
            throw new EmptyTranslatedFieldException();
        }

        $translatedValue = clone $previousFieldValue;
        $translatedValue->alternativeText = $value;

        return $translatedValue;
    }
}
