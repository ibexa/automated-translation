<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation;

use Ibexa\AutomatedTranslation\Encoder;
use Ibexa\AutomatedTranslation\Encoder\Field\FieldEncoderManager;
use Ibexa\AutomatedTranslation\Encoder\Field\RichTextFieldEncoder;
use Ibexa\AutomatedTranslation\Encoder\Field\TextBlockFieldEncoder;
use Ibexa\AutomatedTranslation\Encoder\Field\TextLineFieldEncoder;
use Ibexa\AutomatedTranslation\Encoder\RichText\RichTextEncoder;
use Ibexa\AutomatedTranslation\TextFieldCdataCleaner;
use Ibexa\Contracts\AutomatedTranslation\Encoder\Field\FieldEncoderInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\FieldType\TextLine;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\FieldTypePage\FieldType\LandingPage\Value as LandingPageValue;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value as RichTextValue;
use Ibexa\Tests\AutomatedTranslation\PHPUnit\WellFormedXmlAssertTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EncoderTest extends TestCase
{
    use WellFormedXmlAssertTrait;

    private const LANGUAGE_CODE = 'eng-GB';

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
            $fieldEncoderManagerMock,
            new TextFieldCdataCleaner()
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
        $subject = $this->prepareEncoderForTextLines('encoded', ['field_1_textline'], ['field_2_textline']);
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

        $encodeResult = $subject->encode($content);

        $expectedEncodeResult = '<?xml version="1.0"?>
<response><field_1_textline type="Ibexa\\Core\\FieldType\\TextLine\\Value">encoded</field_1_textline><field_2_textline type="Ibexa\\Core\\FieldType\\TextLine\\Value">encoded</field_2_textline></response>
';

        self::assertEquals($expectedEncodeResult, $encodeResult);
    }

    public function testEncodeTextlineWithAmp(): void
    {
        $subject = $this->prepareEncoderForTextLines('Some text 1 & 2', ['field_1_textline']);
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
                    'value' => new TextLine\Value('Some text 1 & 2'),
                ]),
            ],
        ]);
        $encodeResult = $subject->encode($content);

        $expectedEncodeResult = '<?xml version="1.0"?>
<response><field_1_textline type="Ibexa\\Core\\FieldType\\TextLine\\Value">Some text 1 &amp; 2</field_1_textline></response>
';

        self::assertEquals($expectedEncodeResult, $encodeResult);
    }

    public function testDecodeFakeCdataDoesNotPrependXmlDeclaration(): void
    {
        $subject = $this->createEncoderWithRealFieldEncoders();
        $content = $this->createContent(['field_1_textline' => new TextLine\Value('Some text 1 & 2')]);

        // the bare '&' parses only because decode() turns the faker tag into a CDATA section first
        $payload = '<?xml version="1.0"?>
<response><field_1_textline type="Ibexa\\Core\\FieldType\\TextLine\\Value"><fakecdata>Some text 1 & 2</fakecdata></field_1_textline></response>
';

        $result = $subject->decode($payload, $content);

        self::assertStringNotContainsString('<?xml', (string) $result['field_1_textline']);
        self::assertSame('Some text 1 & 2', (string) $result['field_1_textline']);
    }

    public function testEncodeTextLineWithSpecialCharactersIsWellFormed(): void
    {
        $subject = $this->createEncoderWithRealFieldEncoders();
        $content = $this->createContent(['field_1_textline' => new TextLine\Value('Tom & Jerry <3')]);

        $payload = $subject->encode($content);

        self::assertStringNotContainsString('fakecdata', $payload);
        self::assertStringContainsString('Tom &amp; Jerry &lt;3', $payload);
        self::assertWellFormedXml($payload);
    }

    /**
     * @dataProvider provideSpecialCharacterValues
     */
    public function testEncodeDecodeRoundTripPreservesSpecialCharacters(string $value): void
    {
        $subject = $this->createEncoderWithRealFieldEncoders();
        $content = $this->createContent(['field_1_textline' => new TextLine\Value($value)]);

        $payload = $subject->encode($content);
        self::assertWellFormedXml($payload);

        $result = $subject->decode($payload, $content);

        self::assertSame($value, (string) $result['field_1_textline']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function provideSpecialCharacterValues(): iterable
    {
        yield 'ampersand' => ['Tom & Jerry'];
        yield 'lower than' => ['a < b'];
        yield 'greater than' => ['b > a'];
        yield 'quotes' => ['He said "hi" and \'bye\''];
        yield 'accented' => ['Café & crème'];
        yield 'cyrillic' => ['Привет & мир'];
        yield 'entity looking text' => ['5 &amp; 6'];
        yield 'mixed' => ['Café <b>& "crème"</b> — Привет'];
    }

    public function testEncodeRichTextKeepsFakeCdata(): void
    {
        $subject = $this->createEncoderWithRealFieldEncoders();
        $content = $this->createContent([
            'field_1_richtext' => new RichTextValue($this->getFixture('testEncodeTwoRichText_field1_richtext.xml')),
        ]);

        $payload = $subject->encode($content);

        self::assertStringContainsString('<fakecdata>', $payload);
        self::assertStringNotContainsString('&lt;section', $payload);
        self::assertWellFormedXml($payload);
    }

    public function testEncodePageFieldKeepsFakeCdata(): void
    {
        $blocksPayload = '<blocks><item key="1"><name>Code</name><attributes>'
            . '<content type="text">Tom &amp; Jerry</content>'
            . '</attributes></item></blocks>';

        $contentTypeServiceMock = $this->getContentTypeServiceMock();
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
                    'fieldTypeIdentifier' => 'ezlandingpage',
                    'isTranslatable' => true,
                ],
            ])
            ->getMockForAbstractClass();

        $contentType->method('getFieldDefinition')->willReturn($fieldDefinition);
        $contentTypeServiceMock->method('loadContentType')->willReturn($contentType);

        $fieldEncoder = $this->createMock(FieldEncoderInterface::class);
        $fieldEncoder->method('canEncode')->willReturn(true);
        $fieldEncoder->method('encode')->willReturn($blocksPayload);

        $fieldEncoderManager = new FieldEncoderManager([$fieldEncoder]);

        $subject = new Encoder(
            $contentTypeServiceMock,
            $this->getMockBuilder(EventDispatcherInterface::class)->getMock(),
            $fieldEncoderManager,
            new TextFieldCdataCleaner()
        );

        $payload = $subject->encode($this->createContent(['field_landing_page' => new LandingPageValue()]));

        self::assertStringContainsString('<fakecdata>', $payload);
        self::assertStringNotContainsString('&lt;blocks', $payload);
        self::assertWellFormedXml($payload);
    }

    /**
     * @param array<string, \Ibexa\Core\FieldType\Value> $fieldValues
     */
    private function createContent(array $fieldValues): Content
    {
        $fields = [];
        foreach ($fieldValues as $identifier => $value) {
            $fields[] = new Field([
                'fieldDefIdentifier' => $identifier,
                'languageCode' => self::LANGUAGE_CODE,
                'value' => $value,
            ]);
        }

        return new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => 1,
                    'contentTypeId' => 123,
                    'mainLanguageCode' => self::LANGUAGE_CODE,
                ]),
            ]),
            'internalFields' => $fields,
        ]);
    }

    private function createEncoderWithRealFieldEncoders(): Encoder
    {
        $contentTypeServiceMock = $this->getContentTypeServiceMock();

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
            ->method('getFieldDefinition')
            ->willReturn($fieldDefinition);

        $contentTypeServiceMock
            ->method('loadContentType')
            ->willReturn($contentType);

        $configResolverMock = $this->getMockBuilder(ConfigResolverInterface::class)->getMock();
        $configResolverMock
            ->method('getParameter')
            ->willReturn([]);

        return new Encoder(
            $contentTypeServiceMock,
            $this->getMockBuilder(EventDispatcherInterface::class)->getMock(),
            new FieldEncoderManager([
                new TextLineFieldEncoder(),
                new TextBlockFieldEncoder(),
                new RichTextFieldEncoder(new RichTextEncoder($configResolverMock)),
            ]),
            new TextFieldCdataCleaner()
        );
    }

    /**
     * @param mixed $contentTypeConsecutive
     */
    private function prepareEncoderForTextLines(string $fieldEncoderReturned, ...$contentTypeConsecutive): Encoder
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
        $expected = count($contentTypeConsecutive);

        $contentType
            ->expects(self::exactly($expected))
            ->method('getFieldDefinition')
            ->withConsecutive(...$contentTypeConsecutive)
            ->willReturnOnConsecutiveCalls($fieldDefinition, $fieldDefinition);

        $contentTypeServiceMock
            ->expects(self::once())
            ->method('loadContentType')
            ->with(123)
            ->willReturn($contentType);

        $fieldEncoderManagerMock
            ->expects(self::exactly($expected))
            ->method('encode')
            ->withAnyParameters()
            ->willReturn($fieldEncoderReturned);

        return new Encoder(
            $contentTypeServiceMock,
            $eventDispatcherMock,
            $fieldEncoderManagerMock,
            new TextFieldCdataCleaner()
        );
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

class_alias(EncoderTest::class, 'EzSystems\EzPlatformAutomatedTranslation\Tests\EncoderTest');
