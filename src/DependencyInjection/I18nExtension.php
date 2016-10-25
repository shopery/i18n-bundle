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
        $locator = new FileLocator(__DIR__ . '/../Resources/config');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load('router.yml');

        $config = $this->processConfiguration(
            new Configuration($this->getAlias()),
            $configs
        );

        $languages = $this->extractLanguages(
            $config['available_languages'],
            $config['default_language']
        );

        $cachePath = $config['cache']['path'];

        $container->getParameterBag()->add([
            'shopery.i18n.available_languages' => $languages,
            'shopery.i18n.cache_dir' => $cachePath,
        ]);
    }

    private function extractLanguages($languages, $defaultLanguage):array
    {
        $languages = array_filter($languages, function ($value) use ($defaultLanguage) {
            return $value !== $defaultLanguage;
        });
        array_unshift($languages, $defaultLanguage);

        return $languages;
    }
}
