<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Shopery\Bundle\I18nBundle\Routing\RouteStrategy\RouteStrategy;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class Router implements RouterInterface
{
    private $locales;
    private $factory;
    private $routeStrategy;
    private $context;

    /** @var RouterInterface */
    private $globalRouter;
    /** @var RouterInterface[] */
    private $localRouters = [];

    public function __construct(
        array $languages,
        RouterFactory $factory,
        RouteStrategy $routeStrategy,
        RequestContext $context = null
    ) {
        $this->locales = $languages;
        $this->factory = $factory;
        $this->routeStrategy = $routeStrategy;
        $this->context = $context;
    }

    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getRouteCollection()
    {
        $collection = new RouteCollection();
        $collection->addCollection($this->globalRouter()->getRouteCollection());
        foreach ($this->localRouters() as $localRouter) {
            $collection->addCollection($localRouter->getRouteCollection());
        }

        return $collection;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (isset($parameters['_locale'])) {
            $router = $this->localRouter($parameters['_locale']);
            $result = $this->tryGenerate($router, $name, $parameters, $referenceType);
            if ($result instanceof \Exception === false) {
                return $result;
            }
        }

        $result = $this->tryGenerate($this->globalRouter(), $name, $parameters, $referenceType);

        $locales = $this->locales;
        reset($locales);
        while ($result instanceof \Exception && current($locales) !== false) {
            $router = $this->localRouter(current($locales));
            $result = $this->tryGenerate($router, $name, $parameters, $referenceType);
            next($locales);
        }

        if ($result instanceof \Exception) {
            throw $result;
        }

        return $result;
    }

    private function tryGenerate(RouterInterface $router, $name, array $parameters, $referenceType)
    {
        try {
            return $router->generate($name, $parameters, $referenceType);
        } catch (RouteNotFoundException $e) {
            return $e;
        }
    }

    public function match($pathInfo)
    {
        $locales = $this->routeStrategy->matchingLocales($pathInfo, $this->locales);
        foreach ($locales as $locale) {
            $router = $this->localRouter($locale);
            try {
                return $router->match($pathInfo);
            } catch (ResourceNotFoundException $e) {
                // Fallback
            }
        }

        return $this->globalRouter()->match($pathInfo);
    }

    private function globalRouter()
    {
        if (!$this->globalRouter) {
            $router = $this->factory->create($this->context);
            $this->globalRouter = $router;
        }

        return $this->globalRouter;
    }

    private function localRouter($locale)
    {
        if (!isset($this->localRouters[$locale])) {
            $router = $this->factory->create($this->context, $locale);
            $this->localRouters[$locale] = $router;
        }

        return $this->localRouters[$locale];
    }

    private function localRouters()
    {
        foreach ($this->locales as $locale) {
            $this->localRouter($locale);
        }

        return $this->localRouters;
    }
}
