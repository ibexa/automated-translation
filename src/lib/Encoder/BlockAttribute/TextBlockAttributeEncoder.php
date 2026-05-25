<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Encoder\BlockAttribute;

use Ibexa\AutomatedTranslation\Encoder\Normalizer\PlainTextTranslatedValueNormalizer;
use Ibexa\Contracts\AutomatedTranslation\Encoder\BlockAttribute\BlockAttributeEncoderInterface;

final class TextBlockAttributeEncoder implements BlockAttributeEncoderInterface
{
    private const TYPE = 'text';

    private PlainTextTranslatedValueNormalizer $translatedValueNormalizer;

    public function __construct(
        PlainTextTranslatedValueNormalizer $translatedValueNormalizer
    ) {
        $this->translatedValueNormalizer = $translatedValueNormalizer;
    }

    public function canEncode(string $type): bool
    {
        return $type === self::TYPE;
    }

    public function canDecode(string $type): bool
    {
        return $type === self::TYPE;
    }

    public function encode($value): string
    {
        return (string) $value;
    }

    public function decode(string $value): string
    {
        return $this->translatedValueNormalizer->normalize($value);
    }
}

class_alias(TextBlockAttributeEncoder::class, 'EzSystems\EzPlatformAutomatedTranslation\Encoder\BlockAttribute\TextBlockAttributeEncoder');
