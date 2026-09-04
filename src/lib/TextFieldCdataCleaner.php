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
use LibXMLError;

final class TextFieldCdataCleaner
{
    /**
     * @param array<string> $preserveCdataForTypes values of the "type" attribute whose CDATA is markup
     */
    public function clear(string $payload, array $preserveCdataForTypes): string
    {
        $dom = $this->loadDocument($payload);
        $this->processCdataNodes($dom, $preserveCdataForTypes);

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

    /**
     * @param array<string> $preserveCdataForTypes
     */
    private function processCdataNodes(DOMDocument $dom, array $preserveCdataForTypes): void
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

            if ($this->shouldReplaceCdata($textNode, $preserveCdataForTypes)) {
                $this->replaceWithTextNode($dom, $textNode);
            }
        }
    }

    /**
     * @param array<string> $preserveCdataForTypes
     */
    private function shouldReplaceCdata(DOMNode $node, array $preserveCdataForTypes): bool
    {
        $parent = $node->parentNode;
        if (!$parent instanceof DOMElement) {
            return false;
        }

        return !in_array($parent->getAttribute('type'), $preserveCdataForTypes, true);
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
