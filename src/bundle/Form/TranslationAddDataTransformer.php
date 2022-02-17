<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\AutomatedTranslation\Form;

use Ibexa\AdminUi\Form\Data\Content\Translation\TranslationAddData as BaseTranslationAddData;
use Ibexa\Bundle\AutomatedTranslation\Form\Data\TranslationAddData;
use Symfony\Component\Form\DataTransformerInterface;

class TranslationAddDataTransformer implements DataTransformerInterface
{
    /**
     * @param \Ibexa\AdminUi\Form\Data\Content\Translation\TranslationAddData $value
     *
     * @return \Ibexa\Bundle\AutomatedTranslation\Form\Data\TranslationAddData
     */
    public function transform($value): BaseTranslationAddData
    {
        /* @var BaseTranslationAddData $value */
        return new TranslationAddData($value->getLocation(), $value->getLanguage(), $value->getBaseLanguage());
    }

    /**
     * @param \Ibexa\Bundle\AutomatedTranslation\Form\Data\TranslationAddData $value
     */
    public function reverseTransform($value): TranslationAddData
    {
        return $value;
    }
}

class_alias(TranslationAddDataTransformer::class, 'EzSystems\EzPlatformAutomatedTranslationBundle\Form\TranslationAddDataTransformer');
