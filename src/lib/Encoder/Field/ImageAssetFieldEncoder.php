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
use Ibexa\Core\FieldType\ImageAsset\Value as ImageAssetValue;
use Ibexa\Core\FieldType\Value;
use InvalidArgumentException;

/**
 * Translates the alternative text override of an image asset, keeping the related asset untouched.
 *
 * Without the override, the alternative text comes from the asset content item, which is translated on its own.
 */
final class ImageAssetFieldEncoder implements FieldEncoderInterface
{
    public function canEncode(Field $field): bool
    {
        $value = $field->value;

        return $value instanceof ImageAssetValue
            && $value->destinationContentId !== null
            && $value->alternativeText !== null
            && trim($value->alternativeText) !== '';
    }

    public function canDecode(string $type): bool
    {
        return ImageAssetValue::class === $type;
    }

    public function encode(Field $field): string
    {
        /** @var ImageAssetValue $value */
        $value = $field->value;

        return (string) $value->alternativeText;
    }

    public function decode(
        string $value,
        $previousFieldValue
    ): Value {
        if (!$previousFieldValue instanceof ImageAssetValue) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unable to decode image asset alternative text, expected previous field value of type %s.',
                    ImageAssetValue::class
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
