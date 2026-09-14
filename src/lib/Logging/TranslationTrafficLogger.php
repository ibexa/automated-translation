<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Logging;

use Ibexa\Contracts\AutomatedTranslation\Client\ClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class TranslationTrafficLogger implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param array{debug?: bool|int|string} $configuration
     */
    public function isDebugEnabled(array $configuration): bool
    {
        if (!array_key_exists('debug', $configuration)) {
            return false;
        }

        return filter_var(
            $configuration['debug'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? false;
    }

    public function logRequest(
        ClientInterface $client,
        string $payload,
        ?string $from,
        string $to,
        bool $debug
    ): void {
        if (null === $this->logger) {
            return;
        }

        $this->logger->info(sprintf(
            'Calling %s for translated content (length %s)',
            $client->getServiceFullName(),
            strlen($payload)
        ));

        if ($debug) {
            $this->logger->debug(sprintf(
                "Payload sent to %s (%s -> %s, %d bytes)\n%s",
                $client->getServiceAlias(),
                $from ?? 'auto',
                $to,
                strlen($payload),
                $payload
            ));
        }
    }

    public function logResponse(
        ClientInterface $client,
        string $payload,
        int $statusCode,
        bool $debug
    ): void {
        if (null === $this->logger) {
            return;
        }

        $this->logger->info(sprintf(
            '%s has returned translated content (length %s)',
            $client->getServiceFullName(),
            strlen($payload)
        ));

        if ($debug) {
            $this->logger->debug(sprintf(
                "Payload received from %s (HTTP %d, %d bytes)\n%s",
                $client->getServiceAlias(),
                $statusCode,
                strlen($payload),
                $payload
            ));
        }
    }
}
