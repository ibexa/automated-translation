<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\AutomatedTranslation\Form\Extension;

use Ibexa\AdminUi\Form\Type\Language\LanguageCreateType as BaseLanguageCreateType;
use JMS\TranslationBundle\Annotation\Desc;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class LanguageCreateType extends AbstractTypeExtension
{
    /** @var array<int, string> */
    private $localeList;

    /**
     * @param array<string, string> $localeList
     */
    public function __construct(array $localeList)
    {
        $this->localeList = array_keys($localeList);
    }

    public static function getExtendedTypes(): iterable
    {
        return [BaseLanguageCreateType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->remove('languageCode');
        $builder->add(
            'languageCode',
            ChoiceType::class,
            [
                'label' => /* @Desc("Language code") */ 'ibexa.language.create.language_code',
                'required' => false,
                'choices' => array_combine($this->localeList, $this->localeList),
                'translation_domain' => 'ibexa_automated_translation',
            ]
        );
    }
}

class_alias(LanguageCreateType::class, 'EzSystems\EzPlatformAutomatedTranslationBundle\Form\Extension\LanguageCreateType');
