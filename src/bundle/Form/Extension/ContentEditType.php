<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\AutomatedTranslation\Form\Extension;

use Ibexa\AdminUi\Form\Data\ContentTranslationData;
use Ibexa\AutomatedTranslation\Translator;
use Ibexa\ContentForms\Form\Type\Content\ContentEditType as BaseContentEditType;
use Ibexa\Contracts\ContentForms\Data\Content\FieldData;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentEditType extends AbstractTypeExtension
{
    /** @var \Ibexa\AutomatedTranslation\Translator */
    private $translator;

    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    private $requestStack;

    /** @var \Ibexa\Contracts\Core\Repository\ContentTypeService */
    private $contentTypeService;

    public function __construct(
        Translator $translator,
        RequestStack $requestStack,
        ContentTypeService $contentTypeService
    ) {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->contentTypeService = $contentTypeService;
    }

    public static function getExtendedTypes(): iterable
    {
        return [BaseContentEditType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var \Ibexa\AdminUi\Form\Data\ContentTranslationData $data */
                $data = $event->getData();
                if (!$data instanceof ContentTranslationData) {
                    return;
                }
                $request = $this->requestStack->getMainRequest();
                if (null === $request) {
                    return;
                }
                if (!$request->query->has('translatorAlias')) {
                    return;
                }
                $fromLanguageCode = $request->attributes->get('fromLanguageCode', null);
                $toLanguageCode = $request->attributes->get('toLanguageCode', null);
                $translatedFields = $this->translator->getTranslatedFields(
                    $fromLanguageCode,
                    $toLanguageCode,
                    $request->query->get('translatorAlias'),
                    $data->content
                );
                $contentType = $this->contentTypeService->loadContentType(
                    $data->content->contentInfo->contentTypeId
                );
                foreach ($data->content->getFieldsByLanguage() as $field) {
                    $fieldDef = $contentType->getFieldDefinition($field->fieldDefIdentifier);
                    if (null === $fieldDef) {
                        continue;
                    }

                    $fieldValue = $translatedFields[$fieldDef->identifier] ??
                                  $data->content->getFieldValue($fieldDef->identifier, $fromLanguageCode);
                    $data->addFieldData(
                        new FieldData(
                            [
                                'fieldDefinition' => $fieldDef,
                                'field' => $field,
                                'value' => $fieldValue,
                            ]
                        )
                    );
                }
                $event->setData($data);
            }
        );
    }
}
