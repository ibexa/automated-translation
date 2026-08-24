<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation;

use DOMCdataSection;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Ibexa\AutomatedTranslation\Exception\CdataCleanupFailedException;
use Ibexa\FieldTypePage\FieldType\LandingPage\Value as LandingPageValue;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value as RichTextValue;
use LibXMLError;

final class TextFieldCdataCleaner
{
    // field values are identified by class, Page Builder block attributes by their short type name
    private const CDATA_PRESERVING_TYPES = [
        RichTextValue::class,
        LandingPageValue::class,
        'richtext',
    ];

    public function clear(string $payload): string
    {
        $dom = $this->loadDocument($payload);
        $this->processCdataNodes($dom);

        return $this->saveDocument($dom);
    }

    private function loadDocument(string $payload): DOMDocument
    {
        $dom = new DOMDocument();

        $useInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            if (!$dom->loadXML($payload)) {
                throw new CdataCleanupFailedException(sprintf(
                    'Unable to load XML payload while removing CDATA: %s',
                    $this->getLibXmlErrorMessage()
                ));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }

        return $dom;
    }

    private function getLibXmlErrorMessage(): string
    {
        $messages = array_map(
            static function (LibXMLError $error): string {
                return trim($error->message);
            },
            libxml_get_errors()
        );

        return implode(', ', $messages);
    }

    private function processCdataNodes(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $textNodes = $xpath->query('//text()');

        if ($textNodes === false) {
            return;
        }

        foreach ($textNodes as $textNode) {
            if (!$textNode instanceof DOMCdataSection) {
                continue;
            }

            if ($this->shouldReplaceCdata($textNode)) {
                $this->replaceWithTextNode($dom, $textNode);
            }
        }
    }

    private function shouldReplaceCdata(DOMNode $node): bool
    {
        $parent = $node->parentNode;
        if (!$parent instanceof DOMElement) {
            return false;
        }

        $type = $parent->getAttribute('type');
        foreach (self::CDATA_PRESERVING_TYPES as $preservedType) {
            // is_a() because field values may be lazy loading proxies, whose class name is generated
            if ($type === $preservedType || is_a($type, $preservedType, true)) {
                return false;
            }
        }

        return true;
    }

    private function replaceWithTextNode(DOMDocument $dom, DOMCdataSection $cdataNode): void
    {
        $newText = $dom->createTextNode($cdataNode->data);

        if ($cdataNode->parentNode !== null) {
            $cdataNode->parentNode->replaceChild($newText, $cdataNode);
        }
    }

    private function saveDocument(DOMDocument $dom): string
    {
        $result = $dom->saveXML();

        if ($result === false) {
            throw new CdataCleanupFailedException('Saving XML failed after removing CDATA.');
        }

        return $result;
    }
}
