<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation;

use Ibexa\AutomatedTranslation\Exception\CdataCleanupFailedException;
use Ibexa\AutomatedTranslation\TextFieldCdataCleaner;
use Ibexa\FieldTypePage\FieldType\LandingPage\Value as LandingPageValue;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value as RichTextValue;
use Ibexa\Tests\AutomatedTranslation\Stubs\LazyLoadingPageValueStub;
use PHPUnit\Framework\TestCase;

final class TextFieldCdataCleanerTest extends TestCase
{
    /**
     * @dataProvider providePlainTextTypes
     */
    public function testClearRemovesCdataForPlainText(string $type): void
    {
        $subject = new TextFieldCdataCleaner();

        $result = $subject->clear($this->buildPayload($type, '<![CDATA[Tom & Jerry <3]]>'));

        self::assertStringNotContainsString('CDATA', $result);
        self::assertStringContainsString('Tom &amp; Jerry &lt;3', $result);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function providePlainTextTypes(): iterable
    {
        yield 'text line value' => ['Ibexa\Core\FieldType\TextLine\Value'];
        yield 'text block value' => ['Ibexa\Core\FieldType\TextBlock\Value'];
        yield 'page builder text attribute' => ['text'];
        yield 'unknown type' => ['whatever'];
    }

    /**
     * @dataProvider providePreservedTypes
     */
    public function testClearKeepsCdataForMarkupValues(string $type): void
    {
        $subject = new TextFieldCdataCleaner();

        $result = $subject->clear(
            $this->buildPayload($type, '<![CDATA[<section><para>lorem ipsum</para></section>]]>')
        );

        self::assertStringContainsString('<![CDATA[<section><para>lorem ipsum</para></section>]]>', $result);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function providePreservedTypes(): iterable
    {
        yield 'rich text field' => [RichTextValue::class];
        yield 'page field' => [LandingPageValue::class];
        yield 'page builder richtext attribute' => ['richtext'];
        yield 'lazy loading proxy of page value' => [LazyLoadingPageValueStub::class];
    }

    public function testClearRemovesCdataWhenTypeAttributeIsAbsent(): void
    {
        $subject = new TextFieldCdataCleaner();

        $result = $subject->clear('<blocks><item><name><![CDATA[Code & Co]]></name></item></blocks>');

        self::assertStringNotContainsString('CDATA', $result);
        self::assertStringContainsString('Code &amp; Co', $result);
    }

    public function testClearOnlyTouchesCdataOfNonPreservedTypes(): void
    {
        $subject = new TextFieldCdataCleaner();

        $payload = '<blocks><item key="1">'
            . '<name><![CDATA[Code & Co]]></name>'
            . '<attributes>'
            . '<content type="text"><![CDATA[Tom & Jerry]]></content>'
            . '<body type="richtext"><![CDATA[<section><para>lorem</para></section>]]></body>'
            . '</attributes></item></blocks>';

        $result = $subject->clear($payload);

        self::assertStringContainsString('Code &amp; Co', $result);
        self::assertStringContainsString('Tom &amp; Jerry', $result);
        self::assertStringContainsString('<![CDATA[<section><para>lorem</para></section>]]>', $result);
    }

    public function testClearThrowsOnMalformedPayload(): void
    {
        $subject = new TextFieldCdataCleaner();

        $this->expectException(CdataCleanupFailedException::class);

        $subject->clear('<response><unclosed></response>');
    }

    private function buildPayload(string $type, string $content): string
    {
        return sprintf('<response><field type="%s">%s</field></response>', htmlspecialchars($type), $content);
    }
}
