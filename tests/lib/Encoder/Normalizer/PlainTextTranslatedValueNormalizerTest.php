<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Encoder\Normalizer;

use Ibexa\AutomatedTranslation\Encoder\Normalizer\PlainTextTranslatedValueNormalizer;
use PHPUnit\Framework\TestCase;

final class PlainTextTranslatedValueNormalizerTest extends TestCase
{
    /**
     * @dataProvider translatedValueProvider
     */
    public function testNormalizeReturnsPlainTextTranslatedValue(
        string $value,
        string $expectedValue
    ): void {
        $normalizer = new PlainTextTranslatedValueNormalizer();

        self::assertSame($expectedValue, $normalizer->normalize($value));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function translatedValueProvider(): iterable
    {
        yield 'ampersand entity' => ['foo &amp; bar', 'foo & bar'];
        yield 'double quote entity' => ['foo &quot;bar&quot;', 'foo "bar"'];
        yield 'apostrophe numeric entity' => ['foo &#039;bar&#039;', "foo 'bar'"];
        yield 'mixed entities' => [
            'foo &amp; &quot;bar&quot; &#039;baz&#039;',
            "foo & \"bar\" 'baz'",
        ];
        yield 'already decoded text' => [
            "foo & \"bar\" 'qux'",
            "foo & \"bar\" 'qux'",
        ];
        yield 'unknown entity' => ['foo &broken; bar', 'foo &broken; bar'];
        yield 'other html5 entities' => ['foo &copy; &nbsp; bar', "foo \u{00A9} \u{00A0} bar"];
        yield 'surrounding whitespace' => [' translated foo ', 'translated foo'];
    }
}
