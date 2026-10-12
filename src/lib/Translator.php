<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface;

class Translator
{
    private TranslatorGuard $guard;

    private LocaleConverterInterface $localeConverter;

    private ClientProvider $clientProvider;

    private Encoder $encoder;

    private ContentService $contentService;

    private ContentTypeService $contentTypeService;

    public function __construct(
        TranslatorGuard $guard,
        LocaleConverterInterface $localeConverter,
        ClientProvider $clientProvider,
        Encoder $encoder,
        ContentService $contentService,
        ContentTypeService $contentTypeService
    ) {
        $this->guard = $guard;
        $this->localeConverter = $localeConverter;
        $this->clientProvider = $clientProvider;
        $this->encoder = $encoder;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getTranslatedFields(
        ?string $from,
        ?string $to,
        string $remoteServiceKey,
        Content $content
    ): array {
        $posixFrom = null;
        if (null !== $from) {
            $this->guard->enforceSourceLanguageVersionExist($content, $from);
            $posixFrom = $this->localeConverter->convertToPOSIX($from);
        }
        $this->guard->enforceTargetLanguageExist((string) $to);

        $sourceContent = $this->guard->fetchContent($content, $from);
        $payload = $this->encoder->encode($sourceContent);
        $posixTo = (string) $this->localeConverter->convertToPOSIX((string) $to);
        $remoteService = $this->clientProvider->get($remoteServiceKey);
        $translatedPayload = $remoteService->translate($payload, $posixFrom, $posixTo);

        return $this->encoder->decode($translatedPayload, $sourceContent);
    }

    public function getTranslatedContent(
        string $from,
        string $to,
        string $remoteServiceKey,
        Content $content
    ): Content {
        $translatedFields = $this->getTranslatedFields($from, $to, $remoteServiceKey, $content);

        $contentDraft = $this->contentService->createContentDraft($content->contentInfo);
        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
        $contentUpdateStruct->initialLanguageCode = $to;

        $contentType = $this->contentTypeService->loadContentType(
            $content->contentInfo->contentTypeId
        );

        foreach ($contentType->getFieldDefinitions() as $field) {
            if (!$field->isTranslatable) {
                continue;
            }

            /** @var FieldDefinition $field */
            $fieldName = $field->identifier;
            $newValue = $translatedFields[$fieldName] ?? $content->getFieldValue($fieldName);
            $contentUpdateStruct->setField($fieldName, $newValue, $to);
        }

        return $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
    }
}

class_alias(Translator::class, 'EzSystems\EzPlatformAutomatedTranslation\Translator');
