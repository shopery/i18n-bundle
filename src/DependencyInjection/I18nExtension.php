<?php

namespace Shopery\Bundle\I18nBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class I18nExtension extends Extension
{
    public function getAlias()
    {
        return 'shopery_i18n';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfig($configs, $container);

        $languages = $this->extractLanguages(
            $config['languages']['available'],
            $config['languages']['default']
        );

        $cachePath = $config['cache']['path'];

        $container->getParameterBag()->add([
            'shopery.i18n.available_languages' => $languages,
            'shopery.i18n.cache_dir' => $cachePath,
        ]);

        $loader = $this->loader($container);
        $loader->load('router.yml');

        if ($config['debug']) {
            $loader->load('debug.yml');
        }

        $container->setAlias(
            'shopery.i18n.route_strategy',
            substr($config['route_strategy'], 1)
        );
    }

    private function extractLanguages($languages, $defaultLanguage)
    {
        $notDefault = function ($value) use ($defaultLanguage) {
            return $value !== $defaultLanguage;
        };

        $languages = array_filter($languages, $notDefault);
        array_unshift($languages, $defaultLanguage);

        return $languages;
    }

    private function loader($container)
    {
        $locator = new FileLocator(__DIR__ . '/../Resources/config');

        return new YamlFileLoader($container, $locator);
    }

    /**
     * @{inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($this->getAlias());
    }

    private function processConfig(array $configs, ContainerBuilder $container)
    {
        return $this->processConfiguration(
            $this->getConfiguration($configs, $container),
            $configs
        );
    }
}
