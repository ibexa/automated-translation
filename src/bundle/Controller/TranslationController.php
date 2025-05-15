<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\AutomatedTranslation\Controller;

use Ibexa\Bundle\AdminUi\Controller\TranslationController as BaseTranslationController;
use Ibexa\Contracts\AdminUi\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TranslationController extends Controller
{
    private BaseTranslationController $translationController;

    public function __construct(BaseTranslationController $translationController)
    {
        $this->translationController = $translationController;
    }

    public function addAction(Request $request): Response
    {
        $response = $this->translationController->addAction($request);

        if (!$response instanceof RedirectResponse) {
            return $response;
        }

        $targetUrl = $response->getTargetUrl();
        $contentTranslatePattern = str_replace(
            '/',
            '\/?',
            urldecode(
                $this->generateUrl(
                    'ibexa.content.translate',
                    [
                        'contentId' => '([0-9]*)',
                        'fromLanguageCode' => '([a-zA-Z-]*)',
                        'toLanguageCode' => '([a-zA-Z-]*)',
                    ]
                )
            )
        );

        // admin-ui v3.3.6 introduces different route `ibexa.content.translate_with_location.proxy`
        // when translated content is created.
        $contentTranslateWithLocationPattern = str_replace(
            '/',
            '\/?',
            urldecode(
                $this->generateUrl(
                    'ibexa.content.translate_with_location.proxy',
                    [
                        'contentId' => '([0-9]*)',
                        'fromLanguageCode' => '([a-zA-Z-]*)',
                        'toLanguageCode' => '([a-zA-Z-]*)',
                        'locationId' => '([0-9]*)',
                    ]
                )
            )
        );

        /** @var array{'base_language': string, 'language': string, translatorAlias?: string} $translationParam */
        $translationParam = $request->request->all('add-translation');
        $serviceAlias = $translationParam['translatorAlias'] ?? '';

        if ('' === $serviceAlias || (
            !$this->targetUrlContainsPattern($targetUrl, $contentTranslatePattern) &&
            !$this->targetUrlContainsPattern($targetUrl, $contentTranslateWithLocationPattern)
        )) {
            return $response;
        }

        $response->setTargetUrl(sprintf('%s?translatorAlias=%s', $targetUrl, $serviceAlias));

        return $response;
    }

    public function removeAction(Request $request): Response
    {
        return $this->translationController->removeAction($request);
    }

    private function targetUrlContainsPattern(string $targetUrl, string $pattern): bool
    {
        return 1 === preg_match("#{$pattern}#", $targetUrl);
    }
}
