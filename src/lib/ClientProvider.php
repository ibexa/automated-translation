<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation;

use Ibexa\Contracts\AutomatedTranslation\Client\ClientInterface;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

class ClientProvider
{
    /** @var ClientInterface[] */
    private array $clients = [];

    private ConfigResolverInterface $configResolver;

    /**
     * @param iterable|ClientInterface[] $clients
     */
    public function __construct(
        iterable $clients,
        ConfigResolverInterface $configResolver
    ) {
        $this->configResolver = $configResolver;
        foreach ($clients as $client) {
            $this->addClient($client);
        }
    }

    /**
     * @param ClientInterface $client
     *
     * @throws \ReflectionException
     *
     * @return ClientProvider
     */
    private function addClient(ClientInterface $client): self
    {
        $configurations = $this->configResolver->getParameter('configurations', 'ibexa_automated_translation');
        $reflection = new \ReflectionClass($client);
        $key = strtolower($reflection->getShortName());
        if (isset($configurations[$key])) {
            $client->setConfiguration($configurations[$key]);
            $this->clients[$key] = $client;
        }

        return $this;
    }

    public function get(string $key): ClientInterface
    {
        if (!isset($this->clients[$key])) {
            throw new \LogicException("The Remote Service {$key} does not exist or has not been configured.");
        }

        return $this->clients[$key];
    }

    /**
     * @return ClientInterface[]
     */
    public function getClients(): array
    {
        return $this->clients;
    }
}

class_alias(ClientProvider::class, 'EzSystems\EzPlatformAutomatedTranslation\ClientProvider');
