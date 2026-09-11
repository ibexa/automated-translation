<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Logging;

use Ibexa\AutomatedTranslation\Logging\TranslationTrafficLogger;
use Ibexa\Contracts\AutomatedTranslation\Client\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TranslationTrafficLoggerTest extends TestCase
{
    private const PAYLOAD = '<response><title>Tom &amp; Jerry</title></response>';
    private const TRANSLATED_PAYLOAD = '<response><title>Tom &amp; Jerry DE</title></response>';

    /**
     * The wording of these two records is relied on by existing log processing, so it must not change.
     */
    public function testEmitsUnchangedInfoRecordsWithoutDebug(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Calling Stub Service for translated content (length ' . strlen(self::PAYLOAD) . ')'],
                ['Stub Service has returned translated content (length ' . strlen(self::TRANSLATED_PAYLOAD) . ')']
            );
        $logger
            ->expects(self::never())
            ->method('debug');

        $subject = $this->createSubject($logger);
        $subject->logRequest($this->createClient(), self::PAYLOAD, 'en_GB', 'DE', false);
        $subject->logResponse($this->createClient(), self::TRANSLATED_PAYLOAD, 200, false);
    }

    public function testLogsPayloadsWhenDebugEnabled(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::exactly(2))
            ->method('info');
        $logger
            ->expects(self::exactly(2))
            ->method('debug')
            ->withConsecutive(
                [self::logicalAnd(
                    self::stringContains('Payload sent to stub (en_GB -> DE, ' . strlen(self::PAYLOAD) . ' bytes)'),
                    self::stringContains(self::PAYLOAD)
                )],
                [self::logicalAnd(
                    self::stringContains('Payload received from stub (HTTP 200, ' . strlen(self::TRANSLATED_PAYLOAD) . ' bytes)'),
                    self::stringContains(self::TRANSLATED_PAYLOAD)
                )]
            );

        $subject = $this->createSubject($logger);
        $subject->logRequest($this->createClient(), self::PAYLOAD, 'en_GB', 'DE', true);
        $subject->logResponse($this->createClient(), self::TRANSLATED_PAYLOAD, 200, true);
    }

    public function testReportsMissingSourceLanguage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('debug')
            ->with(self::stringContains('Payload sent to stub (auto -> DE'));

        $this->createSubject($logger)->logRequest($this->createClient(), self::PAYLOAD, null, 'DE', true);
    }

    /**
     * @dataProvider provideDebugValues
     *
     * @param array{debug?: bool|int|string} $configuration
     */
    public function testDebugFlagParsing(array $configuration, bool $expected): void
    {
        self::assertSame($expected, (new TranslationTrafficLogger())->isDebugEnabled($configuration));
    }

    /**
     * @return iterable<string, array{array{debug?: bool|int|string}, bool}>
     */
    public function provideDebugValues(): iterable
    {
        yield 'absent' => [[], false];
        yield 'true' => [['debug' => true], true];
        yield 'string true' => [['debug' => 'true'], true];
        yield 'string one' => [['debug' => '1'], true];
        yield 'integer one' => [['debug' => 1], true];
        yield 'on' => [['debug' => 'on'], true];
        yield 'false' => [['debug' => false], false];
        yield 'string false' => [['debug' => 'false'], false];
        yield 'off' => [['debug' => 'off'], false];
        yield 'not a boolean' => [['debug' => 'maybe'], false];
    }

    public function testDoesNotFailWithoutLogger(): void
    {
        $subject = new TranslationTrafficLogger();

        $subject->logRequest($this->createClient(), self::PAYLOAD, 'en_GB', 'DE', true);
        $subject->logResponse($this->createClient(), self::TRANSLATED_PAYLOAD, 200, true);

        $this->expectNotToPerformAssertions();
    }

    private function createSubject(LoggerInterface $logger): TranslationTrafficLogger
    {
        $subject = new TranslationTrafficLogger();
        $subject->setLogger($logger);

        return $subject;
    }

    private function createClient(): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('getServiceAlias')->willReturn('stub');
        $client->method('getServiceFullName')->willReturn('Stub Service');

        return $client;
    }
}
