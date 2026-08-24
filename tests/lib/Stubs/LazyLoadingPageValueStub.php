<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Stubs;

use Ibexa\FieldTypePage\FieldType\LandingPage\Value as LandingPageValue;

/**
 * Stands in for the lazy loading proxy the Page field type resolves to at runtime, whose generated
 * class name never matches the value class by name.
 */
final class LazyLoadingPageValueStub extends LandingPageValue
{
}
