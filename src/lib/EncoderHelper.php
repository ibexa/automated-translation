<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation;

use DOMCdataSection;
use DOMDocument;
use DOMXPath;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value as RichTextValue;
use RuntimeException;

class EncoderHelper
{
    public function clearCDATAInTextField(string $payload): string
    {
        $dom = new DOMDocument();
        $dom->loadXML($payload);
        $xpath = new DOMXPath($dom);
        $textNodes = $xpath->query('//text()');
        if ($textNodes === false) {
            return $payload;
        }

        foreach ($textNodes as $textNode) {
            if (!$textNode instanceof DOMCdataSection) {
                continue;
            }
            $parent = $textNode->parentNode;
            if ($parent === null) {
                continue;
            }

            $type = $parent->getAttribute('type');

            if ($type !== RichTextValue::class) {
                $newText = $dom->createTextNode($textNode->data);
                $parent->replaceChild($newText, $textNode);
            }
        }
        $payload = $dom->saveXML();

        if (!$payload) {
            throw new RuntimeException(
                sprintf('Saving XML failed after removing CDATA, error: %s', preg_last_error_msg())
            );
        }

        return $payload;
    }
}
