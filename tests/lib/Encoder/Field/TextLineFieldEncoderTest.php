<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Encoder\Field;

use Ibexa\AutomatedTranslation\Encoder\Field\TextLineFieldEncoder;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\TextLine;
use PHPUnit\Framework\TestCase;

class TextLineFieldEncoderTest extends TestCase
{
    public function testEncode(): void
    {
        $field = new Field([
            'fieldDefIdentifier' => 'field_1_textline',
            'value' => new TextLine\Value('Some text 1'),
        ]);

        $subject = new TextLineFieldEncoder();
        $result = $subject->encode($field);

        self::assertEquals('Some text 1', $result);
    }

    public function testDecode(): void
    {
        $field = new Field([
            'fieldDefIdentifier' => 'field_1_textline',
            'value' => new TextLine\Value('Some text 1'),
        ]);

        $subject = new TextLineFieldEncoder();
        $result = $subject->decode('Some text 1', $field->value);

        self::assertInstanceOf(TextLine\Value::class, $result);
        self::assertEquals(new TextLine\Value('Some text 1'), $result);
    }
}
