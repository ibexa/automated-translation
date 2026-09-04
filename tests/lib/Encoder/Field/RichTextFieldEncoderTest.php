<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Encoder\Field;

use Ibexa\AutomatedTranslation\Encoder\Field\RichTextFieldEncoder;
use Ibexa\AutomatedTranslation\Encoder\RichText\RichTextEncoder;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\FieldTypeRichText\FieldType\RichText;
use PHPUnit\Framework\TestCase;

class RichTextFieldEncoderTest extends TestCase
{
    public function testEncode(): void
    {
        $richTextEncoderMock = $this->getMockBuilder(RichTextEncoder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $richTextEncoderMock
            ->expects($this->atLeastOnce())
            ->method('encode')
            ->withAnyParameters()
            ->willReturn('Some text 1');

        $xml1 = $this->getFixture('testEncodeTwoRichText_field1_richtext.xml');

        $field = new Field([
            'fieldDefIdentifier' => 'field_1_richtext',
            'value' => new RichText\Value($xml1),
        ]);

        $subject = new RichTextFieldEncoder($richTextEncoderMock);
        $result = $subject->encode($field);

        $this->assertEquals('Some text 1', $result);
    }

    public function testDecode(): void
    {
        $xml1 = $this->getFixture('testEncodeTwoRichText_field1_richtext.xml');

        $richTextEncoderMock = $this->getMockBuilder(RichTextEncoder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $richTextEncoderMock
            ->expects($this->atLeastOnce())
            ->method('decode')
            ->withAnyParameters()
            ->willReturn($xml1);

        $field = new Field([
            'fieldDefIdentifier' => 'field_1_richtext',
            'value' => new RichText\Value($xml1),
        ]);

        $subject = new RichTextFieldEncoder($richTextEncoderMock);
        $result = $subject->decode(
            $xml1,
            $field->value
        );

        $this->assertInstanceOf(RichText\Value::class, $result);
        $this->assertEquals(new RichText\Value($xml1), $result);
    }

    public function testDecodeRestoresUtf8Encoding(): void
    {
        $decodedXml = '<section xmlns="http://docbook.org/ns/docbook" version="5.0-variant ezpublish-1.0">'
            . '<para>Café &amp; crème — Привет</para></section>';

        $configResolver = $this->createMock(ConfigResolverInterface::class);
        $configResolver->method('getParameter')->willReturn([]);

        $subject = new RichTextFieldEncoder(new RichTextEncoder($configResolver));
        $result = $subject->decode($decodedXml, new RichText\Value($decodedXml));

        $storedXml = (string) $result;

        self::assertStringContainsString('encoding="UTF-8"', $storedXml);
        self::assertStringContainsString('Café', $storedXml);
        self::assertStringContainsString('Привет', $storedXml);
        self::assertStringNotContainsString('&#x', $storedXml);
    }

    protected function getFixture(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/../../../fixtures/' . $name);
    }
}

class_alias(RichTextFieldEncoderTest::class, 'EzSystems\EzPlatformAutomatedTranslation\Tests\Encoder\Field\RichTextFieldEncoderTest');
