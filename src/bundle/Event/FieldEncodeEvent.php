<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\AutomatedTranslation\Event;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;

final class FieldEncodeEvent
{
    private Field $field;

    private string $value;

    public function __construct(
        Field $field,
        string $value
    ) {
        $this->field = $field;
        $this->value = $value;
    }

    public function getField(): Field
    {
        return $this->field;
    }

    public function setField(Field $field): void
    {
        $this->field = $field;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
