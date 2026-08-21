<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Stubs;

use Ibexa\AutomatedTranslation\Client\TranslationTrafficLoggerTrait;
use Psr\Log\LoggerAwareInterface;

/**
 * Exposes the trait's private methods so the log records can be asserted without an HTTP boundary.
 */
final class TranslationTrafficLoggerStub implements LoggerAwareInterface
{
    use TranslationTrafficLoggerTrait;

    public function getServiceAlias(): string
    {
        return 'stub';
    }

    public function getServiceFullName(): string
    {
        return 'Stub Service';
    }

    /**
     * @param array{debug?: bool|int|string} $configuration
     */
    public function configure(array $configuration): void
    {
        $this->configureDebug($configuration);
    }

    public function logRequest(string $payload, ?string $from, string $to): void
    {
        $this->logTranslationRequest($payload, $from, $to);
    }

    public function logResponse(string $payload, int $statusCode): void
    {
        $this->logTranslationResponse($payload, $statusCode);
    }
}
