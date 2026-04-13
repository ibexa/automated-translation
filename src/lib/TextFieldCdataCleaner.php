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
use Ibexa\FieldTypeRichText\FieldType\RichText\Value as RichTextValue;
use RuntimeException;

final class TextFieldCdataCleaner
{
    public function clear(string $payload): string
    {
        $dom = $this->loadDocument($payload);
        $this->processCdataNodes($dom);

        return $this->saveDocument($dom);
    }

    private function loadDocument(string $payload): DOMDocument
    {
        $dom = new DOMDocument();
        $dom->loadXML($payload);

        return $dom;
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

        return $parent->getAttribute('type') !== RichTextValue::class;
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
            throw new RuntimeException('Saving XML failed after removing CDATA.');
        }

        return $result;
    }
}
