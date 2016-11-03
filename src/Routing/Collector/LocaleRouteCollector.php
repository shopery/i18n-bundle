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
        $localeCollections = $this->createLocaleCollections();

        foreach ($source->all() as $name => $route) {
            if ($this->isGlobal($route)) {
                $global->add($name, $route);
                $this->addGlobalRouteTo($name, $route, $localeCollections);
            } else {
                $this->addRouteTranslatedTo($name, $route, $localeCollections);
            }
        }

        foreach ($localeCollections as $locale => $collection) {
            $this->factory->dump($collection, $locale);
        }

        $allProcessedRoutes = [$global];
        foreach ($localeCollections as $locale => $collection) {
            $processed = new RouteCollection();
            $this->transferResources($collection, $processed);
            foreach ($collection->all() as $name => $route) {
                if (!$this->isGlobal($route)) {
                    $processed->add($this->prefixedRouteName($locale, $name), $route);
                }
            }

            $allProcessedRoutes[] = $processed;
        }

        return array_values($allProcessedRoutes);
    }

    /**
     * @param string $name
     * @param Route $sourceRoute
     * @param RouteCollection[] $collections
     */
    private function addRouteTranslatedTo($name, Route $sourceRoute, array $collections)
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

    /**
     * @param string $name
     * @param Route $sourceRoute
     * @param RouteCollection[] $collections
     */
    private function addGlobalRouteTo($name, Route $sourceRoute, array $collections)
    {
        foreach ($this->locales as $locale) {

            $route = clone $sourceRoute;
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
        foreach ($collections as $collection) {
            $result->addCollection($collection);
        }
    }

    /**
     * @param string $locale
     * @param string $routeName
     *
     * @return string
     */
    private function prefixedRouteName($locale, $routeName)
    {
        return 'i18n_'.$locale.'__'.$routeName;
    }
}
