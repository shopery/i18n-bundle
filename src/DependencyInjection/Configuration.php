<?php

namespace Shopery\Bundle\I18nBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private $alias;

    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $this->setNodes(
            $builder->root($this->alias)
        );

        return $builder;
    }

    private function setNodes(ArrayNodeDefinition $root)
    {
        $root
            ->append($this->addLanguagesSetup())
            ->append($this->addRouteStrategySetup())
            ->append($this->addCacheSetup())
            ->append($this->addDebugSetup())
        ;
    }

    private function addLanguagesSetup()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('languages');

        $validateLanguage = function ($value) {
            if (is_string($value) && preg_match('~^[a-z]{2}(_[A-Z]{2})?~', $value) !== false) {
                return $value;
            }

            throw new \InvalidArgumentException(sprintf('Language %s is not valid', json_encode($value)));
        };

        $root
            ->children()
                ->arrayNode('available')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')
                        ->validate()
                            ->always($validateLanguage)
        ;

        $root
            ->children()
                ->scalarNode('default')
                    ->defaultValue('%kernel.locale%')
                    ->validate()
                        ->always($validateLanguage)
        ;

        return $root;
    }

    private function addRouteStrategySetup()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('route_strategy', 'scalar');

        $knownStrategies = [
            'prefix_path_always' => '@shopery.i18n.route_strategy.prefixed_path_always',
            'prefix_path_but_default' => '@shopery.i18n.route_strategy.prefixed_path_but_default',
        ];

        $root
            ->beforeNormalization()
                ->ifString()
                ->then(function ($v) use ($knownStrategies) {
                    if (isset($knownStrategies[$v])) {
                        return $knownStrategies[$v];
                    }

                    return $v;
                })
            ->end()
            ->validate()
                ->ifTrue(function ($v) {
                    return !is_string($v) || $v[0] !== '@';
                })
                ->thenInvalid('Expected a known route strategy, or service reference (starting with "@"), found %s')
        ;

        return $root;
    }

    private function addCacheSetup()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('cache');

        $root
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('path')
                    ->defaultValue('%kernel.cache_dir%')
        ;

        return $root;
    }

    private function addDebugSetup()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('debug', 'boolean');

        $root
            ->defaultValue('%kernel.debug%')
        ;

        return $root;
    }
}
