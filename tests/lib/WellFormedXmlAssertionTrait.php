<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation;

use DOMDocument;
use LibXMLError;

trait WellFormedXmlAssertionTrait
{
    private function assertWellFormedXml(string $payload): void
    {
        $useInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $loaded = (new DOMDocument())->loadXML($payload);
        $errors = array_map(
            static function (LibXMLError $error): string {
                return trim($error->message);
            },
            libxml_get_errors()
        );

        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        self::assertTrue(
            $loaded,
            sprintf("Payload is not well-formed XML:\n%s\n%s", $payload, implode("\n", $errors))
        );
    }
}
