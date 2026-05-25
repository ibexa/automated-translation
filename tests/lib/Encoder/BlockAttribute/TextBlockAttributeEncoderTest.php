<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Encoder\BlockAttribute;

use Ibexa\AutomatedTranslation\Encoder\BlockAttribute\TextBlockAttributeEncoder;
use Ibexa\AutomatedTranslation\Encoder\Normalizer\PlainTextTranslatedValueNormalizer;
use PHPUnit\Framework\TestCase;

final class TextBlockAttributeEncoderTest extends TestCase
{
    public function testDecodeNormalizesTranslatedHtmlEntities(): void
    {
        $encoder = new TextBlockAttributeEncoder(
            new PlainTextTranslatedValueNormalizer()
        );

        self::assertSame(
            "foo & \"bar\" 'baz'",
            $encoder->decode(' foo &amp; &quot;bar&quot; &#039;baz&#039; ')
        );
    }
}
