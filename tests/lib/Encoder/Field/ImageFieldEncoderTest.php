<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Encoder\Field;

use Ibexa\AutomatedTranslation\Encoder\Field\ImageFieldEncoder;
use Ibexa\AutomatedTranslation\Exception\EmptyTranslatedFieldException;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\Image;
use Ibexa\Core\FieldType\TextLine;
use Ibexa\Core\FieldType\Value;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ImageFieldEncoderTest extends TestCase
{
    private ImageFieldEncoder $subject;

    protected function setUp(): void
    {
        $this->subject = new ImageFieldEncoder();
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
        yield 'image with alternative text' => [self::createImageValue('A red apple'), true];
        yield 'image without alternative text' => [self::createImageValue(null), false];
        yield 'image with blank alternative text' => [self::createImageValue('   '), false];
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
        yield 'image value' => [Image\Value::class, true];
        yield 'text line value' => [TextLine\Value::class, false];
    }

    public function testEncode(): void
    {
        $field = $this->createField(self::createImageValue('A red apple & a pear'));

        self::assertSame('A red apple & a pear', $this->subject->encode($field));
    }

    public function testDecodeReplacesOnlyAlternativeText(): void
    {
        $previousValue = self::createImageValue('A red apple');

        $result = $this->subject->decode(' Czerwone jabłko ', $previousValue);

        $expected = self::createImageValue('Czerwone jabłko');
        self::assertEquals($expected, $result);
        self::assertNotSame($previousValue, $result);
        self::assertSame('A red apple', $previousValue->alternativeText);
    }

    public function testDecodeThrowsOnEmptyTranslation(): void
    {
        $this->expectException(EmptyTranslatedFieldException::class);

        $this->subject->decode('  ', self::createImageValue('A red apple'));
    }

    public function testDecodeThrowsWhenPreviousValueIsNotImage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->subject->decode('Czerwone jabłko', new TextLine\Value('A red apple'));
    }

    private function createField(Value $value): Field
    {
        return new Field([
            'fieldDefIdentifier' => 'image',
            'value' => $value,
        ]);
    }

    private static function createImageValue(?string $alternativeText): Image\Value
    {
        return new Image\Value([
            'id' => '1/2/3/apple.png',
            'alternativeText' => $alternativeText,
            'fileName' => 'apple.png',
            'fileSize' => 1024,
            'uri' => '/var/site/storage/images/1/2/3/apple.png',
            'imageId' => '123-456',
            'width' => 640,
            'height' => 480,
            'mime' => 'image/png',
        ]);
    }
}
