<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\PHPUnit;

use Ibexa\Tests\AutomatedTranslation\PHPUnit\Constraint\IsWellFormedXml;

trait WellFormedXmlAssertTrait
{
    public static function assertWellFormedXml(string $payload, string $message = ''): void
    {
        self::assertThat($payload, new IsWellFormedXml(), $message);
    }
}
