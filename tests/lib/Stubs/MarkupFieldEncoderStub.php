<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Stubs;

use Ibexa\Contracts\AutomatedTranslation\Encoder\Field\FieldEncoderInterface;
use Ibexa\Contracts\AutomatedTranslation\Encoder\MarkupEncoderInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\Value;
use LogicException;

final class MarkupFieldEncoderStub implements FieldEncoderInterface, MarkupEncoderInterface
{
    private string $encoded;

    public function __construct(string $encoded)
    {
        $this->encoded = $encoded;
    }

    public function canEncode(Field $field): bool
    {
        return true;
    }

    public function canDecode(string $type): bool
    {
        return true;
    }

    public function encode(Field $field): string
    {
        return $this->encoded;
    }

    /**
     * @param mixed $previousFieldValue
     */
    public function decode(string $value, $previousFieldValue): Value
    {
        throw new LogicException('This stub only covers the encoding side.');
    }
}
