<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Encoder\RichText;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

final class RichTextEncoder
{
    private const CDATA_FAKER_TAG = 'fake_rt_cdata';

    private const XML_MARKUP = '<?xml version="1.0" encoding="UTF-8"?>';

    /**
     * Allow to replace characters preserve eZ RichText Content.
     *
     * @var array<string>
     */
    private array $nonTranslatableCharactersHashMap;

    /**
     * Everything inside these tags must be preserve from translation.
     *
     * @var array<string>
     */
    private array $nonTranslatableTags;

    /**
     * Everything inside these self-closing tags must be preserve from translation.
     *
     * @var array<string>
     */
    private array $nonTranslatableSelfClosingTags;

    /**
     * Every attributes inside these tags must be preserve from translation.
     *
     * @var array<string>
     */
    private array $nonValidAttributeTags;

    /**
     * @var array<string>
     */
    private array $placeHolderMap;

    public function __construct(
        ConfigResolverInterface $configResolver
    ) {
        $tags = $configResolver->getParameter(
            'non_translatable_tags',
            'ibexa.automated_translation.site_access.config'
        );

        $selfClosedTags = $configResolver->getParameter(
            'non_translatable_self_closed_tags',
            'ibexa.automated_translation.site_access.config'
        );

        $chars = $configResolver->getParameter(
            'non_translatable_characters',
            'ibexa.automated_translation.site_access.config'
        );

        $attributes = $configResolver->getParameter(
            'non_valid_attribute_tags',
            'ibexa.automated_translation.site_access.config'
        );

        $this->nonTranslatableTags = ['ezvalue', 'ezconfig', 'ezembed'] + $tags;
        $this->nonTranslatableSelfClosingTags = ['ezembedinline'] + $selfClosedTags;
        $this->nonTranslatableCharactersHashMap = [
                "\n" => '<XEOL />',
                '<section xmlns="http://docbook.org/ns/docbook"'
                . ' xmlns:xlink="http://www.w3.org/1999/xlink"'
                . ' xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml"'
                . ' xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom"'
                . ' version="5.0-variant ezpublish-1.0">' => '<section ATTR1="1">',
            ] + $chars;
        $this->nonValidAttributeTags = ['title'] + $attributes;
        $this->placeHolderMap = [];
    }

    public function encode(string $xmlString): string
    {
        if (strpos($xmlString, self::XML_MARKUP . "\n") !== false) {
            $xmlString = substr($xmlString, strlen(self::XML_MARKUP . "\n"));
        }

        $xmlString = $this->encodeNonTranslatableCharacters($xmlString);

        foreach ($this->nonTranslatableSelfClosingTags as $tag) {
            $xmlString = (string) preg_replace_callback(
                '#<' . $tag . '(.[^>]*)?/>#Uuim',
                function ($matches) use ($tag): string {
                    $hash = $this->hash($matches[0]);
                    $this->placeHolderMap[$hash] = $matches[0];

                    return "<{$tag}>{$hash}</{$tag}>";
                },
                $xmlString
            );
        }

        foreach ($this->nonTranslatableTags as $tag) {
            $xmlString = (string) preg_replace_callback(
                '#<' . $tag . '(>| (.[^>]*)?>)((?:.|\n)*)</' . $tag . '>#Uuim',
                function ($matches) use ($tag): string {
                    $hash = $this->hash($matches[0]);
                    $this->placeHolderMap[$hash] = $matches[0];

                    return "<{$tag}>{$hash}</{$tag}>";
                },
                $xmlString
            );
        }

        foreach ($this->nonValidAttributeTags as $tag) {
            $xmlString = (string) preg_replace_callback(
                '#<' . $tag . '(.[^>]*)>#Uuim',
                function ($matches) use ($tag): string {
                    $hash = $this->hash($matches[0]);
                    $this->placeHolderMap[$hash] = $matches[0];

                    return "<fake{$tag} {$hash}=\"1\">";
                },
                $xmlString
            );
            $xmlString = str_replace("</{$tag}>", "</fake{$tag}>", (string) $xmlString);
        }

        return str_replace(
            ['<![CDATA[', ']]>'],
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            $xmlString
        );
    }

    public function decode(string $value): string
    {
        $value = str_replace(
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            ['<![CDATA[', ']]>'],
            $value
        );

        $value = $this->decodeNonTranslatableCharacters($value);

        foreach (array_reverse($this->nonTranslatableSelfClosingTags) as $tag) {
            $value = (string) preg_replace_callback(
                '#<' . $tag . '>(.*)</' . $tag . '>#Uuim',
                function (array $matches) {
                    return $this->placeHolderMap[trim($matches[1])];
                },
                $value
            );
        }

        foreach (array_reverse($this->nonTranslatableTags) as $tag) {
            $value = (string) preg_replace_callback(
                '#<' . $tag . '>(.*)</' . $tag . '>#Uuim',
                function (array $matches) {
                    return $this->placeHolderMap[trim($matches[1])];
                },
                $value
            );
        }

        foreach ($this->nonValidAttributeTags as $tag) {
            $value = (string) preg_replace_callback(
                '#<fake' . $tag . '(.[^>]*)>#Uuim',
                function (array $matches) {
                    return $this->placeHolderMap[trim(str_replace('="1"', '', $matches[1]))];
                },
                $value
            );
            $value = str_replace("</fake{$tag}>", "</{$tag}>", $value);
        }

        return $this->decodeNonTranslatableCharacters($value);
    }

    private function hash(string $data): string
    {
        return 'H' . substr(md5($data), 0, 8);
    }

    private function encodeNonTranslatableCharacters(string $value): string
    {
        return str_replace(
            array_keys($this->nonTranslatableCharactersHashMap),
            array_values($this->nonTranslatableCharactersHashMap),
            $value
        );
    }

    private function decodeNonTranslatableCharacters(string $value): string
    {
        return str_replace(
            array_values($this->nonTranslatableCharactersHashMap),
            array_keys($this->nonTranslatableCharactersHashMap),
            $value
        );
    }
}
