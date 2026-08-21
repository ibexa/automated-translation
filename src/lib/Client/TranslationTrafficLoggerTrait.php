<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Client;

use Psr\Log\LoggerAwareTrait;

trait TranslationTrafficLoggerTrait
{
    use LoggerAwareTrait;

    private bool $debug = false;

    abstract public function getServiceAlias(): string;

    abstract public function getServiceFullName(): string;

    /**
     * @param array{debug?: bool|int|string} $configuration
     */
    private function configureDebug(array $configuration): void
    {
        if (array_key_exists('debug', $configuration)) {
            $this->debug = filter_var(
                $configuration['debug'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? false;
        }
    }

    private function logTranslationRequest(string $payload, ?string $from, string $to): void
    {
        if (null === $this->logger) {
            return;
        }

        $this->logger->info(sprintf(
            'Calling %s for translated content (length %s)',
            $this->getServiceFullName(),
            strlen($payload)
        ));

        if ($this->debug) {
            $this->logger->debug(sprintf(
                "Payload sent to %s (%s -> %s, %d bytes)\n%s",
                $this->getServiceAlias(),
                $from ?? 'auto',
                $to,
                strlen($payload),
                $payload
            ));
        }
    }

    private function logTranslationResponse(string $payload, int $statusCode): void
    {
        if (null === $this->logger) {
            return;
        }

        $this->logger->info(sprintf(
            '%s has returned translated content (length %s)',
            $this->getServiceFullName(),
            strlen($payload)
        ));

        if ($this->debug) {
            $this->logger->debug(sprintf(
                "Payload received from %s (HTTP %d, %d bytes)\n%s",
                $this->getServiceAlias(),
                $statusCode,
                strlen($payload),
                $payload
            ));
        }
    }
}
