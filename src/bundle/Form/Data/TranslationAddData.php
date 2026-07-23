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
    protected ?string $translatorAlias = null;

    public function getTranslatorAlias(): ?string
    {
        return $this->translatorAlias;
    }

    public function setTranslatorAlias(?string $translatorAlias): void
    {
        $this->translatorAlias = $translatorAlias;
    }
}
