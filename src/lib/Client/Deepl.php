<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Client;

use GuzzleHttp\Client;
use Ibexa\AutomatedTranslation\Exception\ClientNotConfiguredException;
use Ibexa\AutomatedTranslation\Exception\InvalidLanguageCodeException;
use Ibexa\AutomatedTranslation\Logging\TranslationTrafficLogger;
use Ibexa\Contracts\AutomatedTranslation\Client\ClientInterface;

class Deepl implements ClientInterface
{
    /**
     * List of available codes https://developers.deepl.com/docs/resources/supported-languages.
     */
    private const LANGUAGE_CODES = [
        'AR', 'BG', 'CS', 'DA', 'DE', 'EL', 'EN-GB', 'EN-US', 'ES', 'ET', 'FI', 'FR',
        'HU', 'ID', 'IT', 'JA', 'KO', 'LT', 'LV', 'NB', 'NL', 'PL', 'PT-BR', 'PT-PT', 'RO',
        'RU', 'SK', 'SL', 'SV', 'TR', 'UK', 'ZH-HANS', 'ZH-HANT',
    ];

    private string $authKey;

    /** @var array<string, string> */
    private array $languageMap;

    private TranslationTrafficLogger $trafficLogger;

    private bool $debug = false;

    /**
     * @param array<string, string> $languageMap
     */
    public function __construct(array $languageMap, TranslationTrafficLogger $trafficLogger)
    {
        $this->languageMap = $languageMap;
        $this->trafficLogger = $trafficLogger;
    }

    public function getServiceAlias(): string
    {
        return 'deepl';
    }

    public function getServiceFullName(): string
    {
        return 'Deepl';
    }

    /**
     * @param array{authKey?: string, debug?: bool|int|string} $configuration
     */
    public function setConfiguration(array $configuration): void
    {
        if (!isset($configuration['authKey'])) {
            throw new ClientNotConfiguredException('authKey is required');
        }
        $this->authKey = $configuration['authKey'];

        $this->debug = $this->trafficLogger->isDebugEnabled($configuration);
    }

    public function translate(string $payload, ?string $from, string $to): string
    {
        $this->trafficLogger->logRequest($this, $payload, $from, $to, $this->debug);

        $parameters = [
            'target_lang' => $this->normalized($to),
            'tag_handling' => 'xml',
            'text' => $payload,
        ];

        if (null !== $from) {
            $parameters += [
                'source_lang' => substr($this->normalized($from), 0, 2),
            ];
        }

        $http = new Client(
            [
                'base_uri' => 'https://api.deepl.com',
                'timeout' => 5.0,
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $this->authKey,
                ],
            ]
        );
        $response = $http->post('/v2/translate', ['form_params' => $parameters]);
        // May use the native json method from guzzle
        $json = json_decode($response->getBody()->getContents());
        $translatedText = $json->translations[0]->text;

        $this->trafficLogger->logResponse($this, $translatedText, $response->getStatusCode(), $this->debug);

        return $translatedText;
    }

    public function supportsLanguage(string $languageCode): bool
    {
        try {
            return \in_array($this->normalized($languageCode), self::LANGUAGE_CODES);
        } catch (InvalidLanguageCodeException $e) {
            return false;
        }
    }

    private function normalized(string $languageCode): string
    {
        if (\in_array($languageCode, self::LANGUAGE_CODES)) {
            return $languageCode;
        }

        $code = strtoupper(substr($languageCode, 0, 2));
        if (\in_array($code, self::LANGUAGE_CODES)) {
            return $code;
        }

        $languageCode = strtoupper($languageCode);

        if (isset($this->languageMap[$languageCode])) {
            return $this->languageMap[$languageCode];
        }

        if (isset($this->languageMap[$code])) {
            return $this->languageMap[$code];
        }

        throw new InvalidLanguageCodeException($languageCode, $this->getServiceAlias());
    }
}

class_alias(Deepl::class, 'EzSystems\EzPlatformAutomatedTranslation\Client\Deepl');
