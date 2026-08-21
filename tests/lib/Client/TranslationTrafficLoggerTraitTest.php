<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Client;

use Ibexa\Tests\AutomatedTranslation\Stubs\TranslationTrafficLoggerStub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TranslationTrafficLoggerTraitTest extends TestCase
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

        $subject = $this->createSubject($logger, []);
        $subject->logRequest(self::PAYLOAD, 'en_GB', 'DE');
        $subject->logResponse(self::TRANSLATED_PAYLOAD, 200);
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

        $subject = $this->createSubject($logger, ['debug' => true]);
        $subject->logRequest(self::PAYLOAD, 'en_GB', 'DE');
        $subject->logResponse(self::TRANSLATED_PAYLOAD, 200);
    }

    public function testReportsMissingSourceLanguage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('debug')
            ->with(self::stringContains('Payload sent to stub (auto -> DE'));

        $this->createSubject($logger, ['debug' => true])->logRequest(self::PAYLOAD, null, 'DE');
    }

    /**
     * @dataProvider provideDebugValues
     *
     * @param array{debug?: bool|int|string} $configuration
     */
    public function testDebugFlagParsing(array $configuration, bool $expectsPayload): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($expectsPayload ? self::once() : self::never())
            ->method('debug');

        $this->createSubject($logger, $configuration)->logRequest(self::PAYLOAD, 'en_GB', 'DE');
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
        $subject = new TranslationTrafficLoggerStub();
        $subject->configure(['debug' => true]);

        $subject->logRequest(self::PAYLOAD, 'en_GB', 'DE');
        $subject->logResponse(self::TRANSLATED_PAYLOAD, 200);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @param array{debug?: bool|int|string} $configuration
     */
    private function createSubject(LoggerInterface $logger, array $configuration): TranslationTrafficLoggerStub
    {
        $subject = new TranslationTrafficLoggerStub();
        $subject->setLogger($logger);
        $subject->configure($configuration);

        return $subject;
    }
}
