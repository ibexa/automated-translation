<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Encoder\Field;

use Ibexa\AutomatedTranslation\Encoder\Field\TextBlockFieldEncoder;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\TextBlock;
use PHPUnit\Framework\TestCase;

final class TextBlockFieldEncoderTest extends TestCase
{
    private const TEXT_BLOCK_VALUE = "Some text.\nSome more text.";

    public function testEncode(): void
    {
        $field = new Field([
            'fieldDefIdentifier' => 'field_1_TextBlock',
            'value' => new TextBlock\Value(self::TEXT_BLOCK_VALUE),
        ]);

        $subject = new TextBlockFieldEncoder();
        $result = $subject->encode($field);

        $this->assertEquals(self::TEXT_BLOCK_VALUE, $result);
    }

    public function testDecode(): void
    {
        $field = new Field([
            'fieldDefIdentifier' => 'field_1_TextBlock',
            'value' => new TextBlock\Value(self::TEXT_BLOCK_VALUE),
        ]);

        $subject = new TextBlockFieldEncoder();
        $result = $subject->decode(self::TEXT_BLOCK_VALUE, $field->value);

        $this->assertInstanceOf(TextBlock\Value::class, $result);
        $this->assertEquals(new TextBlock\Value(self::TEXT_BLOCK_VALUE), $result);
    }
}
