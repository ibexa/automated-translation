<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\AutomatedTranslation\Encoder\Field;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\Value;

interface FieldEncoderInterface
{
    public function canEncode(Field $field): bool;

    public function canDecode(string $type): bool;

    public function encode(Field $field): string;

    /**
     * @param mixed $previousFieldValue
     */
    public function decode(
        string $value,
        $previousFieldValue
    ): Value;
}

class_alias(FieldEncoderInterface::class, 'EzSystems\EzPlatformAutomatedTranslation\Encoder\Field\FieldEncoderInterface');
