<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\AutomatedTranslation\Form\Extension;

use Ibexa\AdminUi\Form\Type\Content\Translation\TranslationAddType as BaseTranslationAddType;
use Ibexa\AutomatedTranslation\ClientProvider;
use Ibexa\Bundle\AutomatedTranslation\Form\TranslationAddDataTransformer;
use Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationAddType extends AbstractTypeExtension
{
    /** @var \Ibexa\AutomatedTranslation\ClientProvider */
    private $clientProvider;

    /** @var \Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface */
    private $localeConverter;

    private TranslatorInterface $translator;

    public function __construct(
        ClientProvider $clientProvider,
        LocaleConverterInterface $localeConverter,
        TranslatorInterface $translator
    ) {
        $this->clientProvider = $clientProvider;
        $this->localeConverter = $localeConverter;
        $this->translator = $translator;
    }

    public static function getExtendedTypes(): iterable
    {
        return [BaseTranslationAddType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $clients = $this->clientProvider->getClients();
        $clientsCount = count($clients);

        if ($clientsCount <= 0) {
            return;
        }
        if (1 === $clientsCount) {
            $client = array_pop($clients);
            $builder
                ->add(
                    'translatorAlias',
                    CheckboxType::class,
                    [
                        'label' => $client->getServiceFullName(),
                        'value' => $client->getServiceAlias(),
                        'data' => false,
                        'required' => false,
                        'disabled' => true,
                    ]
                );
            $builder->addModelTransformer(new TranslationAddDataTransformer());

            return;
        }

        $choices = [];
        $choices[$this->translator->trans('automated_translation.no_service', [], 'ibexa_automated_translation')] = TranslationAddDataTransformer::NO_SERVICE;

        foreach ($this->clientProvider->getClients() as $client) {
            $choices[$client->getServiceFullName()] = $client->getServiceAlias();
        }

        $builder
            ->add(
                'translatorAlias',
                ChoiceType::class,
                [
                    'label' => false,
                    'expanded' => false,
                    'multiple' => false,
                    'required' => false,
                    'choices' => $choices,
                    'disabled' => false,
                    'placeholder' => false,
                ]
            );
        $builder->addModelTransformer(new TranslationAddDataTransformer());
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // let's pass to the template/form the possible language
        $map = [];

        $fillMap = function ($key, &$map) use ($form) {
            $languages = $form->get($key);
            $choices = $languages->getConfig()->getAttribute('choice_list')->getChoices();
            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Language $language */
            foreach ($choices as $language) {
                foreach ($this->clientProvider->getClients() as $client) {
                    $posix = $this->localeConverter->convertToPOSIX($language->languageCode);
                    if (null === $posix) {
                        continue;
                    }
                    if ($client->supportsLanguage($posix)) {
                        $map[$client->getServiceAlias()][] = $language->languageCode;
                    }
                }
            }
        };

        $fillMap('language', $map);
        $fillMap('base_language', $map);

        $supportedProviderMap = [];
        foreach ($map as $provider => $languages) {
            $supportedProviderMap["data-supported-translation-languages-$provider"] = implode(' ', $languages);
        }

        $view->vars['autotranslated_data'] = $map;
        $view->vars['supported_provider_map'] = $supportedProviderMap;
        parent::buildView($view, $form, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}

class_alias(TranslationAddType::class, 'EzSystems\EzPlatformAutomatedTranslationBundle\Form\Extension\TranslationAddType');
