<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\PHPUnit\Constraint;

use DOMDocument;
use LibXMLError;
use PHPUnit\Framework\Constraint\Constraint;

final class IsWellFormedXml extends Constraint
{
    /** @var array<string> */
    private array $errors = [];

    public function toString(): string
    {
        return 'is well-formed XML';
    }

    /**
     * @param mixed $other
     */
    protected function matches($other): bool
    {
        $useInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $loaded = (new DOMDocument())->loadXML((string) $other);
        $this->errors = array_map(
            static function (LibXMLError $error): string {
                return trim($error->message);
            },
            libxml_get_errors()
        );

        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        return $loaded;
    }

    /**
     * @param mixed $other
     */
    protected function additionalFailureDescription($other): string
    {
        return implode("\n", $this->errors);
    }
}
