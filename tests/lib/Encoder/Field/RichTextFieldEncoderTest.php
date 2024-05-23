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
            ->expects(self::atLeastOnce())
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

        self::assertEquals('Some text 1', $result);
    }

    public function testDecode(): void
    {
        $xml1 = $this->getFixture('testEncodeTwoRichText_field1_richtext.xml');

        $richTextEncoderMock = $this->getMockBuilder(RichTextEncoder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $richTextEncoderMock
            ->expects(self::atLeastOnce())
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

        self::assertInstanceOf(RichText\Value::class, $result);
        self::assertEquals(new RichText\Value($xml1), $result);
    }

    protected function getFixture(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/../../../fixtures/' . $name);
    }
}
