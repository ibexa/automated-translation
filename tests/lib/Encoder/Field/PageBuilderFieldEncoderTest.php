<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Encoder\Field;

use Ibexa\AutomatedTranslation\Encoder\BlockAttribute\BlockAttributeEncoderManager;
use Ibexa\AutomatedTranslation\Encoder\Field\PageBuilderFieldEncoder;
use Ibexa\AutomatedTranslation\TextFieldCdataCleaner;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\FieldTypePage\FieldType\LandingPage\Model\Attribute;
use Ibexa\Contracts\FieldTypePage\FieldType\LandingPage\Model\BlockValue;
use Ibexa\Contracts\FieldTypePage\FieldType\LandingPage\Model\Page;
use Ibexa\Contracts\FieldTypePage\FieldType\LandingPage\Model\Zone;
use Ibexa\Contracts\FieldTypePage\FieldType\Page\Block\Definition\BlockAttributeDefinition;
use Ibexa\Contracts\FieldTypePage\FieldType\Page\Block\Definition\BlockDefinition;
use Ibexa\FieldTypePage\FieldType\LandingPage\Value;
use Ibexa\FieldTypePage\FieldType\Page\Block\Definition\BlockDefinitionFactoryInterface;
use Ibexa\Tests\AutomatedTranslation\PHPUnit\WellFormedXmlAssertTrait;
use PHPUnit\Framework\TestCase;

final class PageBuilderFieldEncoderTest extends TestCase
{
    use WellFormedXmlAssertTrait;

    public const ATTRIBUTE_VALUE = 'ibexa';
    public const ATTRIBUTE_VALUE_CDATA = 'ibexa & ibexa';

    private const BLOCK_NAME_WITH_AMP = 'Code & Co';
    private const TEXT_ATTRIBUTE_VALUE = 'Tom & Jerry <3 Café';
    private const RICHTEXT_ATTRIBUTE_VALUE = '<section ATTR1="1"><para>Tom &amp; Jerry &lt;3 Café</para></section>';

    /** @var \Ibexa\AutomatedTranslation\Encoder\BlockAttribute\BlockAttributeEncoderManager&\PHPUnit\Framework\MockObject\MockObject */
    private BlockAttributeEncoderManager $blockAttributeEncoderManagerMock;

    /** @var \Ibexa\FieldTypePage\FieldType\Page\Block\Definition\BlockDefinitionFactoryInterface&\PHPUnit\Framework\MockObject\MockObject */
    private BlockDefinitionFactoryInterface $blockDefinitionFactoryMock;

    public function setUp(): void
    {
        $this->blockAttributeEncoderManagerMock = $this->createMock(BlockAttributeEncoderManager::class);
        $this->blockDefinitionFactoryMock = $this->createMock(BlockDefinitionFactoryInterface::class);
    }

    public function testEncode(): void
    {
        $this->blockDefinitionFactoryMock
            ->method('getBlockDefinition')
            ->withAnyParameters()
            ->willReturn($this->getBlockDefinition());

        $this->blockAttributeEncoderManagerMock
            ->method('encode')
            ->withAnyParameters()
            ->willReturn(self::ATTRIBUTE_VALUE);

        $field = $this->getLandingPageField();
        $subject = new PageBuilderFieldEncoder(
            $this->blockAttributeEncoderManagerMock,
            $this->blockDefinitionFactoryMock,
            new TextFieldCdataCleaner()
        );

        $result = $subject->encode($field);

        self::assertEquals($this->getEncodeResult(), $result);
    }

    public function testEncodeMissingAttribute(): void
    {
        $this->blockDefinitionFactoryMock
            ->method('getBlockDefinition')
            ->withAnyParameters()
            ->willReturn($this->createBlockDefinition());

        $this->blockAttributeEncoderManagerMock
            ->method('encode')
            ->withAnyParameters()
            ->willReturn(self::ATTRIBUTE_VALUE);

        $field = $this->getLandingPageField();
        $subject = new PageBuilderFieldEncoder(
            $this->blockAttributeEncoderManagerMock,
            $this->blockDefinitionFactoryMock,
            new TextFieldCdataCleaner()
        );

        $result = $subject->encode($field);
        $expectedResult = '<blocks><item key="1"><name>Code</name><attributes/></item></blocks>
';

        self::assertEquals($expectedResult, $result);
    }

    public function testEncodeCDATAInTextField(): void
    {
        $this->blockDefinitionFactoryMock
            ->method('getBlockDefinition')
            ->withAnyParameters()
            ->willReturn($this->getBlockDefinition());

        $this->blockAttributeEncoderManagerMock
            ->method('encode')
            ->withAnyParameters()
            ->willReturn(self::ATTRIBUTE_VALUE_CDATA);

        $field = $this->getLandingPageField();
        $subject = new PageBuilderFieldEncoder(
            $this->blockAttributeEncoderManagerMock,
            $this->blockDefinitionFactoryMock,
            new TextFieldCdataCleaner()
        );

        $result = $subject->encode($field);
        $encodedValue = htmlspecialchars(self::ATTRIBUTE_VALUE_CDATA);

        self::assertEquals($this->getEncodeResult($encodedValue), $result);
    }

    public function testCanEncode(): void
    {
        $field = $this->getLandingPageField();
        $subject = new PageBuilderFieldEncoder(
            $this->blockAttributeEncoderManagerMock,
            $this->blockDefinitionFactoryMock,
            new TextFieldCdataCleaner()
        );

        self::assertTrue($subject->canEncode($field));
    }

    public function testDecode(): void
    {
        $this->blockAttributeEncoderManagerMock
            ->expects(self::atLeastOnce())
            ->method('decode')
            ->withAnyParameters()
            ->willReturn(self::ATTRIBUTE_VALUE);

        $field = $this->getLandingPageField();
        $subject = new PageBuilderFieldEncoder(
            $this->blockAttributeEncoderManagerMock,
            $this->blockDefinitionFactoryMock,
            new TextFieldCdataCleaner()
        );

        $result = $subject->decode(
            $this->getEncodeResult(),
            $field->value
        );

        self::assertInstanceOf(Value::class, $result);
        self::assertEquals(new Value($this->getPage()), $result);
    }

    public function testCanDecode(): void
    {
        $field = $this->getLandingPageField();
        $subject = new PageBuilderFieldEncoder(
            $this->blockAttributeEncoderManagerMock,
            $this->blockDefinitionFactoryMock,
            new TextFieldCdataCleaner()
        );

        self::assertTrue($subject->canDecode(get_class($field->value)));
    }

    public function testEncodeRichTextAttributeKeepsCdataWrapper(): void
    {
        $payload = $this->encodeBlockWith(self::RICHTEXT_ATTRIBUTE_VALUE, 'richtext');

        self::assertStringContainsString('<fake_blocks_cdata>', $payload);
        self::assertStringContainsString('<section', $payload);
        self::assertStringNotContainsString('&lt;section', $payload);
        self::assertWellFormedXml($payload);
    }

    public function testEncodeDecodeRichTextAttributeWithSpecialCharacters(): void
    {
        $payload = $this->encodeBlockWith(self::RICHTEXT_ATTRIBUTE_VALUE, 'richtext');

        self::assertWellFormedXml($payload);

        // escaping is symmetric: the decode assertions below pass with or without the wrapper
        self::assertStringContainsString('<fake_blocks_cdata>', $payload);
        self::assertStringNotContainsString('&lt;section', $payload);

        $result = $this->decodeBlockPayload($payload, self::RICHTEXT_ATTRIBUTE_VALUE);
        $page = $result->getPage();

        self::assertNotNull($page);

        $attribute = $page->getBlockById('1')->getAttribute('content');
        self::assertNotNull($attribute);
        self::assertSame(self::RICHTEXT_ATTRIBUTE_VALUE, $attribute->getValue());
    }

    public function testEncodeDecodeTextAttributeWithSpecialCharacters(): void
    {
        $payload = $this->encodeBlockWith(self::TEXT_ATTRIBUTE_VALUE, 'text');

        self::assertStringNotContainsString('fake_blocks_cdata', $payload);
        self::assertStringContainsString('Tom &amp; Jerry &lt;3', $payload);
        self::assertWellFormedXml($payload);

        $result = $this->decodeBlockPayload($payload, self::TEXT_ATTRIBUTE_VALUE);
        $page = $result->getPage();

        self::assertNotNull($page);
        self::assertSame(self::BLOCK_NAME_WITH_AMP, $page->getBlockById('1')->getName());

        $attribute = $page->getBlockById('1')->getAttribute('content');
        self::assertNotNull($attribute);
        self::assertSame(self::TEXT_ATTRIBUTE_VALUE, $attribute->getValue());
    }

    private function encodeBlockWith(string $attributeValue, string $attributeType): string
    {
        $this->blockDefinitionFactoryMock
            ->method('getBlockDefinition')
            ->withAnyParameters()
            ->willReturn($this->getBlockDefinition($attributeType));

        $this->blockAttributeEncoderManagerMock
            ->method('encode')
            ->withAnyParameters()
            ->willReturnArgument(1);

        $this->blockAttributeEncoderManagerMock
            ->method('producesMarkup')
            ->willReturnCallback(static function (string $type): bool {
                return $type === 'richtext';
            });

        $subject = new PageBuilderFieldEncoder(
            $this->blockAttributeEncoderManagerMock,
            $this->blockDefinitionFactoryMock,
            new TextFieldCdataCleaner()
        );

        return $subject->encode($this->getLandingPageField(self::BLOCK_NAME_WITH_AMP, $attributeValue));
    }

    private function decodeBlockPayload(string $payload, string $attributeValue): Value
    {
        $this->blockAttributeEncoderManagerMock
            ->method('decode')
            ->withAnyParameters()
            ->willReturnArgument(1);

        $subject = new PageBuilderFieldEncoder(
            $this->blockAttributeEncoderManagerMock,
            $this->blockDefinitionFactoryMock,
            new TextFieldCdataCleaner()
        );

        $result = $subject->decode(
            $payload,
            $this->getLandingPageField(self::BLOCK_NAME_WITH_AMP, $attributeValue)->value
        );

        self::assertInstanceOf(Value::class, $result);

        return $result;
    }

    private function getLandingPageField(
        string $blockName = 'Code',
        string $attributeValue = self::ATTRIBUTE_VALUE
    ): Field {
        return new Field([
            'fieldDefIdentifier' => 'field_landing_page',
            'value' => new Value($this->getPage($blockName, $attributeValue)),
        ]);
    }

    private function getPage(string $blockName = 'Code', string $attributeValue = self::ATTRIBUTE_VALUE): Page
    {
        return new Page('default', [$this->createZone($blockName, $attributeValue)]);
    }

    private function createZone(string $blockName = 'Code', string $attributeValue = self::ATTRIBUTE_VALUE): Zone
    {
        return new Zone('1', 'Foo', [
            new BlockValue(
                '1',
                'tag',
                $blockName,
                'default',
                null,
                null,
                '',
                null,
                null,
                [
                    new Attribute(
                        '1',
                        'content',
                        $attributeValue
                    ),
                ]
            ),
        ]);
    }

    private function getBlockDefinition(string $attributeType = 'string'): BlockDefinition
    {
        $blockDefinition = $this->createBlockDefinition();

        $attributeDefinitions = [];
        $blockAttributeDefinition = new BlockAttributeDefinition();
        $blockAttributeDefinition->setIdentifier('1');
        $blockAttributeDefinition->setName('content');
        $blockAttributeDefinition->setType($attributeType);
        $blockAttributeDefinition->setConstraints([]);
        $blockAttributeDefinition->setValue(self::ATTRIBUTE_VALUE);
        $blockAttributeDefinition->setCategory('default');
        $blockAttributeDefinition->setOptions([]);

        $attributeDefinitions['content'] = $blockAttributeDefinition;

        $blockDefinition->setAttributes($attributeDefinitions);

        return $blockDefinition;
    }

    private function createBlockDefinition(): BlockDefinition
    {
        $blockDefinition = new BlockDefinition();
        $blockDefinition->setIdentifier('tag');
        $blockDefinition->setName('Code');
        $blockDefinition->setCategory('default');
        $blockDefinition->setThumbnail('fake_thumbnail');
        $blockDefinition->setVisible(true);
        $blockDefinition->setConfigurationTemplate('fake_configuration_template');
        $blockDefinition->setViews([]);

        return $blockDefinition;
    }

    private function getEncodeResult(string $value = self::ATTRIBUTE_VALUE): string
    {
        return '<blocks><item key="1"><name>Code</name><attributes><content type="string">' .
            $value . '</content></attributes></item></blocks>
';
    }
}
