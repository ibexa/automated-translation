<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Encoder\Field;

use Ibexa\AutomatedTranslation\Encoder\Field\ImageAssetFieldEncoder;
use Ibexa\AutomatedTranslation\Exception\EmptyTranslatedFieldException;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\ImageAsset;
use Ibexa\Core\FieldType\TextLine;
use Ibexa\Core\FieldType\Value;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ImageAssetFieldEncoderTest extends TestCase
{
    private const DESTINATION_CONTENT_ID = 42;

    private ImageAssetFieldEncoder $subject;

    protected function setUp(): void
    {
        $this->subject = new ImageAssetFieldEncoder();
    }

    /**
     * @dataProvider provideCanEncodeValues
     */
    public function testCanEncode(
        Value $value,
        bool $expected
    ): void {
        self::assertSame($expected, $this->subject->canEncode($this->createField($value)));
    }

    /**
     * @return iterable<string, array{Value, bool}>
     */
    public static function provideCanEncodeValues(): iterable
    {
        yield 'asset with alternative text' => [
            new ImageAsset\Value(self::DESTINATION_CONTENT_ID, 'A red apple'),
            true,
        ];
        yield 'asset without alternative text' => [
            new ImageAsset\Value(self::DESTINATION_CONTENT_ID, null),
            false,
        ];
        yield 'asset with blank alternative text' => [
            new ImageAsset\Value(self::DESTINATION_CONTENT_ID, '   '),
            false,
        ];
        yield 'alternative text without asset' => [new ImageAsset\Value(null, 'A red apple'), false];
        yield 'text line' => [new TextLine\Value('A red apple'), false];
    }

    /**
     * @dataProvider provideCanDecodeTypes
     */
    public function testCanDecode(
        string $type,
        bool $expected
    ): void {
        self::assertSame($expected, $this->subject->canDecode($type));
    }

    /**
     * @return iterable<string, array{class-string, bool}>
     */
    public static function provideCanDecodeTypes(): iterable
    {
        yield 'image asset value' => [ImageAsset\Value::class, true];
        yield 'text line value' => [TextLine\Value::class, false];
    }

    public function testEncode(): void
    {
        $field = $this->createField(new ImageAsset\Value(self::DESTINATION_CONTENT_ID, 'A red apple & a pear'));

        self::assertSame('A red apple & a pear', $this->subject->encode($field));
    }

    public function testDecodeReplacesOnlyAlternativeText(): void
    {
        $previousValue = new ImageAsset\Value(self::DESTINATION_CONTENT_ID, 'A red apple');

        $result = $this->subject->decode(' Czerwone jabłko ', $previousValue);

        self::assertEquals(new ImageAsset\Value(self::DESTINATION_CONTENT_ID, 'Czerwone jabłko'), $result);
        self::assertNotSame($previousValue, $result);
        self::assertSame('A red apple', $previousValue->alternativeText);
    }

    public function testDecodeThrowsOnEmptyTranslation(): void
    {
        $this->expectException(EmptyTranslatedFieldException::class);

        $this->subject->decode('  ', new ImageAsset\Value(self::DESTINATION_CONTENT_ID, 'A red apple'));
    }

    public function testDecodeThrowsWhenPreviousValueIsNotImageAsset(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->subject->decode('Czerwone jabłko', new TextLine\Value('A red apple'));
    }

    private function createField(Value $value): Field
    {
        return new Field([
            'fieldDefIdentifier' => 'image_asset',
            'value' => $value,
        ]);
    }
}
