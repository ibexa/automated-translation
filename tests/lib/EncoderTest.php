<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation;

use Ibexa\AutomatedTranslation\Encoder;
use Ibexa\AutomatedTranslation\Encoder\Field\FieldEncoderManager;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\TextLine;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EncoderTest extends TestCase
{
    public function testEncodeWithoutFields(): void
    {
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $fieldEncoderManagerMock = $this->getMockBuilder(FieldEncoderManager::class)->getMock();

        $content = new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => 1,
                    'contentTypeId' => 123,
                ]),
            ]),
            'internalFields' => [],
        ]);

        $subject = new Encoder(
            $contentTypeServiceMock,
            $eventDispatcherMock,
            $fieldEncoderManagerMock
        );

        $encodeResult = $subject->encode($content);

        $expected = <<<XML
<?xml version="1.0"?>
<response/>

XML;

        self::assertEquals($expected, $encodeResult);
    }

    public function testEncodeTwoTextline(): void
    {
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $fieldEncoderManagerMock = $this->getMockBuilder(FieldEncoderManager::class)->getMock();

        $contentType = $this->getMockForAbstractClass(
            ContentType::class,
            [],
            '',
            true,
            true,
            true,
            ['getFieldDefinition']
        );
        $fieldDefinition = $this->getMockBuilder(FieldDefinition::class)
            ->setConstructorArgs([
                [
                    'fieldTypeIdentifier' => 'ezstring',
                    'isTranslatable' => true,
                ],
            ])
            ->getMockForAbstractClass();

        $contentType
            ->expects(self::exactly(2))
            ->method('getFieldDefinition')
            ->withConsecutive(['field_1_textline'], ['field_2_textline'])
            ->willReturnOnConsecutiveCalls($fieldDefinition, $fieldDefinition);

        $contentTypeServiceMock
            ->expects(self::once())
            ->method('loadContentType')
            ->with(123)
            ->willReturn($contentType);

        $content = new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => 1,
                    'contentTypeId' => 123,
                ]),
            ]),
            'internalFields' => [
                new Field([
                    'fieldDefIdentifier' => 'field_1_textline',
                    'value' => new TextLine\Value('Some text 1'),
                ]),
                new Field([
                    'fieldDefIdentifier' => 'field_2_textline',
                    'value' => new TextLine\Value('Some text 2'),
                ]),
            ],
        ]);

        $fieldEncoderManagerMock
            ->expects(self::exactly(2))
            ->method('encode')
            ->withAnyParameters()
            ->will(self::returnValue('encoded'));

        $subject = new Encoder(
            $contentTypeServiceMock,
            $eventDispatcherMock,
            $fieldEncoderManagerMock
        );

        $encodeResult = $subject->encode($content);

        $expectedEncodeResult = '<?xml version="1.0"?>
<response><field_1_textline type="Ibexa\\Core\\FieldType\\TextLine\\Value">encoded</field_1_textline><field_2_textline type="Ibexa\\Core\\FieldType\\TextLine\\Value">encoded</field_2_textline></response>
';

        self::assertEquals($expectedEncodeResult, $encodeResult);
    }

    /**
     * Returns ContentTypeService mock object.
     *
     * @return \Ibexa\Contracts\Core\Repository\ContentTypeService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getContentTypeServiceMock()
    {
        return $this
            ->getMockBuilder('Ibexa\\Contracts\\Core\\Repository\\ContentTypeService')
            ->getMock();
    }

    protected function getFixture(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/../fixtures/' . $name);
    }
}
