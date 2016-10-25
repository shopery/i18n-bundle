<?php

namespace Shopery\Bundle\I18nBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class RouterSetupFromFramework implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $this->definition($container, 'router.default', true);
        $arguments = $this->extractArguments($definition, [
            'resource',
            'context',
            'options',
        ]);

        $definition = $this->definition($container, 'shopery.i18n.router');
        $this->injectArguments($definition, [
            'context' => $arguments['context'],
        ]);

        $definition = $this->definition($container, 'shopery.i18n.resource_route_collector');
        $this->injectArguments($definition, [
            'resource' => $arguments['resource'],
            'type' => isset($arguments['options']['type'])
                        ? $arguments['options']['type']
                        : null,
        ]);

        $container->setAlias('router', 'shopery.i18n.router');
    }

    private function definition(ContainerBuilder $container, $name, $clone = false)
    {
        $definition = $container->findDefinition($name);
        $className = $this->definitionClassName($definition, $container);

        if ($clone !== false) {
            $definition = clone $definition;
        }

        $definition->setClass($className);

        return $definition;
    }

    private function extractArguments(Definition $definition, array $parameters)
    {
        $indexByParameter = $this->indexParameters($definition);

        $result = [];
        foreach ($parameters as $parameter) {
            $result[$parameter] = $definition->getArgument(
                $indexByParameter[$parameter]
            );
        }
        return $result;
    }

    private function definitionClassName(Definition $definition, ContainerBuilder $container)
    {
        $className = $definition->getClass();
        for (; empty($className) && $definition instanceof DefinitionDecorator;
               $definition = $container->findDefinition($definition->getParent())) {

            $className = $definition->getClass();
        }

        return $container->getParameterBag()->resolveValue($className);
    }

    private function injectArguments(Definition $definition, array $parameters)
    {
        $indexByParameter = $this->indexParameters($definition);

        foreach ($parameters as $parameterName => $parameterValue) {
            $definition->replaceArgument(
                $indexByParameter[$parameterName],
                $parameterValue
            );
        }
    }

    private function indexParameters(Definition $definition)
    {
        $reflectionClass = new \ReflectionClass($definition->getClass());
        $constructor = $reflectionClass->getConstructor();

        $parameters = [];
        foreach ($constructor->getParameters() as $index => $parameter) {
            $parameters[$parameter->getName()] = $index;
        }

        return $parameters;
    }
}
