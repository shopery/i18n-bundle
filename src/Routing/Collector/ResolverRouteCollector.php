<?php

namespace Shopery\Bundle\I18nBundle\Routing\Collector;

use Shopery\Bundle\I18nBundle\Routing\RouteCollector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

class ResolverRouteCollector implements RouteCollector
{
    private $inner;
    private $container;

    public function __construct(RouteCollector $inner, ContainerInterface $container)
    {
        $this->inner = $inner;
        $this->container = $container;
    }

    public function getRouteCollection()
    {
        return $this->resolveCollection(
            $this->inner->getRouteCollection()
        );
    }

    private function resolveCollection(RouteCollection $collection)
    {
        foreach ($collection as $route) {
            foreach ($route->getDefaults() as $name => $value) {
                $route->setDefault($name, $this->resolve($value));
            }

            foreach ($route->getRequirements() as $name => $value) {
                if ('_scheme' === $name || '_method' === $name) {
                    // ignore deprecated requirements to not trigger deprecation warnings
                    continue;
                }

                $route->setRequirement($name, $this->resolve($value));
            }

            $route->setPath($this->resolve($route->getPath()));
            $route->setHost($this->resolve($route->getHost()));

            $schemes = [];
            foreach ($route->getSchemes() as $scheme) {
                $schemes = array_merge($schemes, explode('|', $this->resolve($scheme)));
            }
            $route->setSchemes($schemes);

            $methods = [];
            foreach ($route->getMethods() as $method) {
                $methods = array_merge($methods, explode('|', $this->resolve($method)));
            }
            $route->setMethods($methods);
            $route->setCondition($this->resolve($route->getCondition()));
        }

        return $collection;
    }

    private function resolve($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->resolve($val);
            }

            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $escapedValue = preg_replace_callback('/%%|%([^%\s]++)%/', function ($match) use ($value) {
            // skip %%
            if (!isset($match[1])) {
                return '%%';
            }

            $resolved = $this->container->getParameter($match[1]);

            if (is_string($resolved) || is_numeric($resolved)) {
                return (string) $resolved;
            }

            throw new \RuntimeException(sprintf(
                    'The container parameter "%s", used in the route configuration value "%s", '.
                    'must be a string or numeric, but it is of type %s.',
                    $match[1],
                    $value,
                    gettype($resolved)
                )
            );

        }, $value);

        return str_replace('%%', '%', $escapedValue);
    }
}
