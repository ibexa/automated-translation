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
use Ibexa\Contracts\AutomatedTranslation\Client\ClientInterface;
use Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TranslationAddType extends AbstractTypeExtension
{
    private ClientProvider $clientProvider;

    private LocaleConverterInterface $localeConverter;

    public function __construct(
        ClientProvider $clientProvider,
        LocaleConverterInterface $localeConverter
    ) {
        $this->clientProvider = $clientProvider;
        $this->localeConverter = $localeConverter;
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
                        'data' => true,
                        'required' => false,
                        'disabled' => true,
                    ]
                );
            $builder->addModelTransformer(new TranslationAddDataTransformer());

            return;
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
                    'choices' => ['' => 'no-service'] + $this->clientProvider->getClients(),
                    'choice_label' => static function ($client) {
                        if ($client instanceof ClientInterface) {
                            return ucfirst($client->getServiceFullName());
                        }

                        return $client;
                    },
                    'choice_value' => static function ($client): string {
                        if ($client instanceof ClientInterface) {
                            return $client->getServiceAlias();
                        }

                        return '';
                    },
                    'disabled' => true,
                ]
            );
        $builder->addModelTransformer(new TranslationAddDataTransformer());
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // let's pass to the template/form the possible language
        $map = [];

        $fillMap = function ($key, array &$map) use ($form): void {
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

        $view->vars['autotranslated_data'] = $map;
        parent::buildView($view, $form, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
