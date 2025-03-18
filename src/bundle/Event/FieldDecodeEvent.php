<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\AutomatedTranslation\Event;

use Ibexa\Core\FieldType\Value;

final class FieldDecodeEvent
{
    private string $type;

    private Value $value;

    /** @var mixed */
    private $previousValue;

    /**
     * @param mixed $previousValue
     */
    public function __construct(
        string $type,
        Value $value,
        $previousValue
    ) {
        $this->type = $type;
        $this->value = $value;
        $this->previousValue = $previousValue;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getValue(): Value
    {
        return $this->value;
    }

    public function setValue(Value $value): void
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getPreviousValue()
    {
        return $this->previousValue;
    }

    /**
     * @param mixed $previousValue
     */
    public function setPreviousValue($previousValue): void
    {
        $this->previousValue = $previousValue;
    }
}
