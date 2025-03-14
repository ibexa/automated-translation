<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Encoder\Field;

use Ibexa\AutomatedTranslation\Encoder\BlockAttribute\BlockAttributeEncoderManager;
use Ibexa\AutomatedTranslation\Encoder\Field\PageBuilderFieldEncoder;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\FieldTypePage\FieldType\LandingPage\Model\Attribute;
use Ibexa\Contracts\FieldTypePage\FieldType\LandingPage\Model\BlockValue;
use Ibexa\Contracts\FieldTypePage\FieldType\LandingPage\Model\Page;
use Ibexa\Contracts\FieldTypePage\FieldType\LandingPage\Model\Zone;
use Ibexa\Contracts\FieldTypePage\FieldType\Page\Block\Definition\BlockAttributeDefinition;
use Ibexa\Contracts\FieldTypePage\FieldType\Page\Block\Definition\BlockDefinition;
use Ibexa\FieldTypePage\FieldType\LandingPage\Value;
use Ibexa\FieldTypePage\FieldType\Page\Block\Definition\BlockDefinitionFactoryInterface;
use PHPUnit\Framework\TestCase;

final class PageBuilderFieldEncoderTest extends TestCase
{
    public const ATTRIBUTE_VALUE = 'ibexa';

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
            $this->blockDefinitionFactoryMock
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
            $this->blockDefinitionFactoryMock
        );

        $result = $subject->encode($field);
        $expectedResult = '<blocks><item key="1"><name>Code</name><attributes/></item></blocks>
';

        self::assertEquals($expectedResult, $result);
    }

    public function testCanEncode(): void
    {
        $field = $this->getLandingPageField();
        $subject = new PageBuilderFieldEncoder(
            $this->blockAttributeEncoderManagerMock,
            $this->blockDefinitionFactoryMock
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
            $this->blockDefinitionFactoryMock
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
            $this->blockDefinitionFactoryMock
        );

        self::assertTrue($subject->canDecode(get_class($field->value)));
    }

    private function getLandingPageField(): Field
    {
        return new Field([
            'fieldDefIdentifier' => 'field_landing_page',
            'value' => new Value($this->getPage()),
        ]);
    }

    private function getPage(): Page
    {
        return new Page('default', [$this->createZone()]);
    }

    private function createZone(): Zone
    {
        return new Zone('1', 'Foo', [
            new BlockValue(
                '1',
                'tag',
                'Code',
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
                        self::ATTRIBUTE_VALUE
                    ),
                ]
            ),
        ]);
    }

    private function getBlockDefinition(): BlockDefinition
    {
        $blockDefinition = $this->createBlockDefinition();

        $attributeDefinitions = [];
        $blockAttributeDefinition = new BlockAttributeDefinition();
        $blockAttributeDefinition->setIdentifier('1');
        $blockAttributeDefinition->setName('content');
        $blockAttributeDefinition->setType('string');
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

    private function getEncodeResult(): string
    {
        return '<blocks><item key="1"><name>Code</name><attributes><content type="string">' .
            self::ATTRIBUTE_VALUE . '</content></attributes></item></blocks>
';
    }
}
