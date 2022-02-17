<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\AutomatedTranslation\Form\Data;

use Ibexa\AdminUi\Form\Data\Content\Translation\TranslationAddData as BaseTranslationAddData;

class TranslationAddData extends BaseTranslationAddData
{
    /** @var string|bool */
    protected $translatorAlias;

    /** @return string|bool */
    public function getTranslatorAlias()
    {
        return $this->translatorAlias;
    }

    /** @param string|bool $translatorAlias */
    public function setTranslatorAlias($translatorAlias): void
    {
        $this->translatorAlias = $translatorAlias;
    }
}

class_alias(TranslationAddData::class, 'EzSystems\EzPlatformAutomatedTranslationBundle\Form\Data\TranslationAddData');
