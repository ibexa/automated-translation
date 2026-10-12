<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Exception;

use InvalidArgumentException;

class InvalidLanguageCodeException extends InvalidArgumentException
{
    public function __construct(
        string $languageCode,
        string $driver
    ) {
        parent::__construct("$languageCode not recognized by $driver");
    }
}

class_alias(InvalidLanguageCodeException::class, 'EzSystems\EzPlatformAutomatedTranslation\Exception\InvalidLanguageCodeException');
