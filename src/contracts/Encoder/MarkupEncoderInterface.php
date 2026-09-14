<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\AutomatedTranslation\Encoder;

/**
 * Marks an encoder whose encoded value is itself XML, so that its CDATA has to survive encoding
 * instead of being escaped as plain text.
 */
interface MarkupEncoderInterface
{
}
