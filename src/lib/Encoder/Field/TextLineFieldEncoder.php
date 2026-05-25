<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Encoder\Field;

use Ibexa\AutomatedTranslation\Encoder\Normalizer\PlainTextTranslatedValueNormalizer;
use Ibexa\AutomatedTranslation\Exception\EmptyTranslatedFieldException;
use Ibexa\Contracts\AutomatedTranslation\Encoder\Field\FieldEncoderInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\FieldType\Value;

final class TextLineFieldEncoder implements FieldEncoderInterface
{
    private PlainTextTranslatedValueNormalizer $translatedValueNormalizer;

    public function __construct(
        PlainTextTranslatedValueNormalizer $translatedValueNormalizer
    ) {
        $this->translatedValueNormalizer = $translatedValueNormalizer;
    }

    public function canEncode(Field $field): bool
    {
        return $field->value instanceof TextLineValue;
    }

    public function canDecode(string $type): bool
    {
        return TextLineValue::class === $type;
    }

    public function encode(Field $field): string
    {
        return (string) $field->value;
    }

    public function decode(string $value, $previousFieldValue): Value
    {
        $value = $this->translatedValueNormalizer->normalize($value);

        if (strlen($value) === 0) {
            throw new EmptyTranslatedFieldException();
        }

        return new TextLineValue($value);
    }
}

class_alias(TextLineFieldEncoder::class, 'EzSystems\EzPlatformAutomatedTranslation\Encoder\Field\TextLineFieldEncoder');
