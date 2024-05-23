<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Encoder\RichText;

use Ibexa\AutomatedTranslation\Encoder\RichText\RichTextEncoder;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use PHPUnit\Framework\TestCase;

class RichTextEncoderTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface */
    private $configResolver;

    public function setUp(): void
    {
        parent::setUp();

        $this->configResolver = $this
            ->getMockBuilder(ConfigResolverInterface::class)
            ->getMock();

        $this->configResolver
            ->expects(self::atLeastOnce())
            ->method('getParameter')
            ->withConsecutive(
                [self::equalTo('non_translatable_tags'), self::equalTo('ibexa.automated_translation.site_access.config')],
                [self::equalTo('non_translatable_self_closed_tags'), self::equalTo('ibexa.automated_translation.site_access.config')],
                [self::equalTo('non_translatable_characters'), self::equalTo('ibexa.automated_translation.site_access.config')],
                [self::equalTo('non_valid_attribute_tags'), self::equalTo('ibexa.automated_translation.site_access.config')]
            )
            ->willReturnOnConsecutiveCalls([], [], [], []);
    }

    public function testEncodeAndDecodeRichtext(): void
    {
        $xml1 = $this->getFixture('testEncodeTwoRichText_field1_richtext.xml');

        $subject = new RichTextEncoder($this->configResolver);

        $encodeResult = $subject->encode($xml1);

        $expected = $this->getFixture('testEncodeTwoRichText_field1_richtext_encoded.xml');

        self::assertEquals($expected, $encodeResult . "\n");

        $decodeResult = $subject->decode($encodeResult);

        self::assertEquals($xml1, $decodeResult);
    }

    public function testEncodeAndDecodeRichtextEmbeded(): void
    {
        $xml1 = $this->getFixture('testEncodeTwoRichTextWithTwoEzembed_field2_richtext.xml');

        $subject = new RichTextEncoder($this->configResolver);

        $encodeResult = $subject->encode($xml1);

        $expected = $this->getFixture('testEncodeTwoRichTextWithTwoEzembed_field2_richtext_encoded.xml');

        self::assertEquals($expected, $encodeResult . "\n");

        $decodeResult = $subject->decode($encodeResult);

        self::assertEquals($xml1, $decodeResult);
    }

    public function testEncodeAndDecodeRichtextExtended(): void
    {
        $xml1 = $this->getFixture('testEncodeRichText_input.xml');

        $subject = new RichTextEncoder($this->configResolver);

        $encodeResult = $subject->encode($xml1);

        $expected = $this->getFixture('testEncodeRichText_input_encoded.xml');

        self::assertEquals(trim($expected), trim($encodeResult));

        $decodeResult = $subject->decode($encodeResult);

        $decodeResult = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $decodeResult;

        self::assertEquals($xml1, $decodeResult);
    }

    protected function getFixture(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/../../../fixtures/' . $name);
    }
}
