<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Shopery\Bundle\I18nBundle\Routing\RouteStrategy\RouteStrategy;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class LocalizedRouter implements RouterInterface
{
    private $routeStrategy;
    private $options;
    private $context;
    /** @var  CachedRouter[] */
    private $inners;

    /**
     * LocalizedRouter constructor.
     * @param array $options
     * @param RequestContext|null $context
     */
    public function __construct(
        RouteStrategy $routeStrategy,
        array $options,
        RequestContext $context = null
    ) {
        $this->routeStrategy = $routeStrategy;
        $this->options = $options;
        $this->context = $context;

        foreach ($routeStrategy->allLocales() as $locale) {
            $this->inners[$locale] = new CachedRouter($options, $context, $locale);
        }
    }

    public function initializeRouteCollection(RouteCollection $collection)
    {
        foreach ($this->inners as $locale => $inner) {

            $innerCollection = new RouteCollection();

            foreach ($collection->all() as $name => $route) {
                $route = clone $route;
                $route->setDefault('_locale', $locale);

                //TODO:: Which translator?
                $translatedPath = $translator->trans($name, [], 'routes', $locale);
                if ($translatedPath !== $name) {
                    $route->setPath($translatedPath);
                }

                $name = $locale.'__'.$name;

                $route->setPath(
                    $this->routeStrategy->pathWithLocale(
                        $route->getPath(),
                        $locale
                    )
                );

                $innerCollection->add($name, $route);
            }

            $inner->initializeRouteCollection($innerCollection);
        }
    }

    public function setContext(RequestContext $context)
    {
        foreach ($this->inners as $inner) {
            $inner->setContext($context);
        }
    }

    public function getContext()
    {
        //All contexts are synchronized, so just return one of them.
        /** @var CachedRouter $anyRouter */
        $anyRouter = array_values($this->inners)[0];

        return $anyRouter->getContext();
    }

    public function getRouteCollection()
    {
        $routes = new RouteCollection();
        foreach ($this->inners as $inner) {
            $routes->addCollection($inner->getRouteCollection());
        }

        return $routes;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        $locale = (isset($parameters['_locale']) && in_array($parameters['_locale'], $this->locales()))
            ? $parameters['_locale']
            : $this->requestLocale();

        return $this->inners[$locale]->generate($name, $parameters, $referenceType);
    }

    public function match($pathinfo)
    {
        $lastError = new RouteNotFoundException(sprintf("No locale can match path: %s", $pathinfo));

        foreach ($this->routeStrategy->localesWhichMayMatchPath($pathinfo) as $locale) {
            try {
                return $this->inners[$locale]->match($pathinfo);
            } catch (RouteNotFoundException $e) {
                $lastError = $e;
            }
        }

        throw $lastError;
    }

    /**
     * @return string[]
     */
    private function locales()
    {
        return array_keys($this->inners);
    }

    /**
     * @return string
     */
    private function requestLocale()
    {
        //TODO:: This properly
        return $this->routeStrategy->allLocales()[0];
    }
}
