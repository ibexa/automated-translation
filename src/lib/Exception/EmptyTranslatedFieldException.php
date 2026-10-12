<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Exception;

use InvalidArgumentException;

class EmptyTranslatedFieldException extends InvalidArgumentException {}

class_alias(EmptyTranslatedFieldException::class, 'EzSystems\EzPlatformAutomatedTranslation\Exception\EmptyTranslatedFieldException');
