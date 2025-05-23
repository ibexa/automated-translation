<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\AutomatedTranslation\DependencyInjection;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use Ibexa\Contracts\AutomatedTranslation\Encoder\BlockAttribute\BlockAttributeEncoderInterface;
use Ibexa\Contracts\AutomatedTranslation\Encoder\Field\FieldEncoderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

class IbexaAutomatedTranslationExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        if (empty($config['system'])) {
            return;
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        // always needed because of Bundle extension.
        $loader->load('services_override.yaml');

        $container->registerForAutoconfiguration(FieldEncoderInterface::class)
            ->addTag('ibexa.automated_translation.field_encoder');

        $container->registerForAutoconfiguration(BlockAttributeEncoderInterface::class)
            ->addTag('ibexa.automated_translation.block_attribute_encoder');
        if (!$this->hasConfiguredClients($config, $container)) {
            return;
        }

        $loader->load('admin_ui.yaml');
        $loader->load('default_settings.yaml');
        $loader->load('services.yaml');

        $processor = new ConfigurationProcessor($container, $this->getAlias());
        $processor->mapSetting('configurations', $config);
        $processor->mapSetting('non_translatable_characters', $config);
        $processor->mapSetting('non_translatable_tags', $config);
        $processor->mapSetting('non_translatable_self_closed_tags', $config);
        $processor->mapSetting('non_valid_attribute_tags', $config);
    }

    /**
     * @param array{system: array{configurations: mixed}} $config
     */
    private function hasConfiguredClients(array $config, ContainerBuilder $container): bool
    {
        return 0 !== count(array_filter($config['system'], static function (array $value) use ($container): ?array {
            return array_filter($value['configurations'], static function ($value) use ($container): bool {
                $value = is_array($value) ? reset($value) : $value;

                return !empty($container->resolveEnvPlaceholders($value, true));
            });
        }));
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependJMSTranslation($container);
    }

    private function prependJMSTranslation(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('jms_translation', [
            'configs' => [
                'ibexa_automated_translation' => [
                    'dirs' => [
                        __DIR__ . '/../../',
                    ],
                    'excluded_dirs' => ['Behat'],
                    'output_dir' => __DIR__ . '/../Resources/translations/',
                    'output_format' => 'xliff',
                ],
            ],
        ]);
    }
}
