<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Encoder\Normalizer;

final class PlainTextTranslatedValueNormalizer
{
    public function normalize(string $value): string
    {
        $decodedValue = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($decodedValue);
    }
}
