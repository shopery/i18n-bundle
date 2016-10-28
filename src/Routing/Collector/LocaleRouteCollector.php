<?php

namespace Shopery\Bundle\I18nBundle\Routing\Collector;

use Shopery\Bundle\I18nBundle\Routing\CachedRouterFactory;
use Shopery\Bundle\I18nBundle\Routing\RouteCollector;
use Shopery\Bundle\I18nBundle\Routing\RouteStrategy\RouteStrategy;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Translation\TranslatorInterface;

class LocaleRouteCollector implements RouteCollector
{
    private $inner;
    private $routeStrategy;
    private $factory;
    private $translator;
    private $locales;

    public function __construct(
        RouteCollector $inner,
        RouteStrategy $routeStrategy,
        CachedRouterFactory $factory,
        TranslatorInterface $translator,
        array $locales
    ) {
        $this->inner = $inner;
        $this->routeStrategy = $routeStrategy;
        $this->factory = $factory;
        $this->translator = $translator;
        $this->locales = $locales;
    }

    public function getRouteCollection()
    {
        $original = $this->inner->getRouteCollection();

        $result = new RouteCollection();
        $this->transferResources($original, $result);

        $splitCollections = $this->splitRoutes($original);
        $this->appendCollections($result, $splitCollections);

        return $result;
    }

    private function transferResources(
        RouteCollection $source,
        RouteCollection $destination
    ) {
        foreach ($source->getResources() as $resource) {
            $destination->addResource($resource);
        }
    }

    private function splitRoutes(RouteCollection $source)
    {
        $global = new RouteCollection();
        $local = $this->createLocaleCollections();

        foreach ($source->all() as $name => $route) {
            if ($this->isGlobal($route)) {
                $global->add($name, $route);
            } else {
                $this->translateRoute($name, $route, $local);
            }
        }

        $this->factory->dump($global, null);
        foreach ($local as $locale => $collection) {
            $this->factory->dump($collection, $locale);
        }

        foreach ($local as $locale => $collection) {
            $processed = new RouteCollection();
            $this->transferResources($collection, $processed);
            foreach ($collection->all() as $name => $route) {
                $processed->add('i18n_' . $locale . '__' . $name, $route);
            }
            $local[$locale] = $processed;
        }

        $local[] = $global;

        return array_values($local);
    }

    /**
     * @param string $name
     * @param Route $sourceRoute
     * @param RouteCollection[] $collections
     */
    private function translateRoute($name, Route $sourceRoute, array $collections)
    {
        foreach ($this->locales as $locale) {

            $route = clone $sourceRoute;
            $route->setDefault('_locale', $locale);

            $translatedPath = $this->translator->trans($name, [], 'routes', $locale);
            if ($translatedPath !== $name) {
                $route->setPath($translatedPath);
            }

            $route->setPath(
                $this->routeStrategy->withLocale(
                    $route->getPath(),
                    $locale,
                    $this->locales
                )
            );

            $collections[$locale]->add($name, $route);
        }
    }

    private function isGlobal(Route $route)
    {
        return $route->getOption('i18n') === false;
    }

    /**
     * @return RouteCollection[]
     */
    private function createLocaleCollections()
    {
        $local = [];
        foreach ($this->locales as $locale) {
            $local[$locale] = new RouteCollection();
        }

        return $local;
    }

    /**
     * @param RouteCollection $result
     * @param RouteCollection[] $collections
     */
    private function appendCollections(RouteCollection $result, array $collections)
    {
        foreach (array_reverse($collections) as $collection) {
            $result->addCollection($collection);
        }
    }
}
