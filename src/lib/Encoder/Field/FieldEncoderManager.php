<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Encoder\Field;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\Value;
use InvalidArgumentException;

final class FieldEncoderManager
{
    /** @var iterable|\Ibexa\Contracts\AutomatedTranslation\Encoder\Field\FieldEncoderInterface[] */
    private iterable $fieldEncoders;

    /**
     * @param iterable|\Ibexa\Contracts\AutomatedTranslation\Encoder\Field\FieldEncoderInterface[] $fieldEncoders
     */
    public function __construct(iterable $fieldEncoders = [])
    {
        $this->fieldEncoders = $fieldEncoders;
    }

    public function encode(Field $field): string
    {
        foreach ($this->fieldEncoders as $fieldEncoder) {
            if ($fieldEncoder->canEncode($field)) {
                return $fieldEncoder->encode($field);
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Unable to encode field %s. Make sure field encoder service for it is properly registered.',
                get_class($field)
            )
        );
    }

    /**
     * @param mixed $previousFieldValue
     *
     * @throws \InvalidArgumentException
     * @throws \Ibexa\AutomatedTranslation\Exception\EmptyTranslatedFieldException
     */
    public function decode(string $type, string $value, $previousFieldValue): Value
    {
        foreach ($this->fieldEncoders as $fieldEncoder) {
            if ($fieldEncoder->canDecode($type)) {
                return $fieldEncoder->decode($value, $previousFieldValue);
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Unable to decode field %s. Make sure field encoder service for it is properly registered.',
                $type
            )
        );
    }
}
