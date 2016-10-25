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
        $validateLanguage = function ($value) {
            if (is_string($value) && preg_match('~^[a-z]{2}(_[A-Z]{2})?~', $value) !== false) {
                return $value;
            }

            throw new \InvalidArgumentException(sprintf('Language %s is not valid', json_encode($value)));
        };

        $root
            ->children()
                ->arrayNode('available_languages')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')
                        ->validate()
                            ->always($validateLanguage)
                        ->end()
                    ->end()
                ->end()

                ->scalarNode('default_language')
                    ->defaultValue('%kernel.locale%')
                    ->validate()
                        ->always($validateLanguage)
                    ->end()
                ->end()

                ->scalarNode('route_strategy')->end()

                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('path')
                            ->defaultValue('%kernel.cache_dir%')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
