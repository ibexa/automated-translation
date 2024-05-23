<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\AutomatedTranslation\DependencyInjection;

use Ibexa\Bundle\AutomatedTranslation\DependencyInjection\IbexaAutomatedTranslationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class IbexaAutomatedTranslationExtensionTest extends TestCase
{
    /**
     * @return array{array{array{system: array{default: array{configurations: array{}}}}, bool}}
     */
    public function clientConfigurationDataProvider(): array
    {
        return [
            //set 1
            [['system' => ['default' => ['configurations' => []]]], false],
            //set 2
            [['system' => ['default' => ['configurations' => [
                'client1' => ['key1' => 'value1'],
            ]]]], true],
            //set 3
            [['system' => ['default' => ['configurations' => [
                'client1' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
            ]]]], true],
            //set 3
            [['system' => ['default' => ['configurations' => [
                'client1' => [
                    'key1' => 'value1',
                    'key2' => 'valueX',
                ],
                'client2' => [
                    'key3' => 'value2',
                    'key2' => 'valueY',
                ],
            ]]]], true],
            //set 4
            [['system' => ['default' => ['configurations' => [
                'client1' => [
                    'key1' => 'value1',
                    'key2' => 'valueX',
                ],
                'client2' => [
                    'key3' => 'value2',
                    'key2' => 'valueY',
                ],
                'client3' => [
                    'key1' => 'ENV_TEST1',
                    'key2' => 'valueX',
                ],
            ]]]], true],
        ];
    }

    /**
     * @param array<mixed> $input
     *
     * @dataProvider clientConfigurationDataProvider
     */
    public function testHasConfiguredClients(array $input, bool $expected): void
    {
        $containerMock = $this->getMockBuilder(ContainerBuilder::class)
            ->onlyMethods(['resolveEnvPlaceholders'])
            ->getMock();

        $containerMock
            ->expects(self::any())
            ->method('resolveEnvPlaceholders')
            ->withConsecutive(['value1'], ['value2'], ['ENV_TEST1'])
            ->willReturnOnConsecutiveCalls(['value1'], ['value2'], ['test1']);

        $subject = new IbexaAutomatedTranslationExtension();

        // call for private method hasConfiguredClients on $subject object
        $hasConfiguredClientsResult = call_user_func_array(\Closure::bind(
            function ($method, $params) {
                return call_user_func_array([$this, $method], $params);
            },
            $subject,
            IbexaAutomatedTranslationExtension::class
        ), ['hasConfiguredClients', [$input, $containerMock]]);

        self::assertEquals($expected, $hasConfiguredClientsResult);
    }
}
