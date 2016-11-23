<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Shopery\Bundle\I18nBundle\Routing\RouteStrategy\RouteStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class Router implements SymfonyRouterInterface
{
    private $locales;
    private $factory;
    private $routeStrategy;
    private $context;

    /** @var SymfonyRouterInterface[] */
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

        foreach ($this->localRouters as $router) {
            $router->setContext($this->context);
        }
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getRouteCollection()
    {
        $collection = new RouteCollection();
        foreach ($this->localRouters() as $localRouter) {
            $collection->addCollection($localRouter->getRouteCollection());
        }

        return $collection;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (isset($parameters['_locale'])) {
            $locale = $parameters['_locale'];
        } else if ($this->context->hasParameter('_locale')) {
            $locale = $this->context->getParameter('_locale');
        } else {
            $locale = reset($this->locales);
        }

        $router = $this->localRouter($locale);

        return $router->generate($name, $parameters, $referenceType);
    }


    public function matchRequest(Request $request)
    {
        $router = $this->localRouterForPathInfo($request->getPathInfo());

        return $router->matchRequest($request);
    }

    public function match($pathInfo)
    {
        $router = $this->localRouterForPathInfo($pathInfo);

        return $router->match($pathInfo);
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

    /**
     * @param string $pathInfo
     * @return SymfonyRouterInterface
     */
    private function localRouterForPathInfo($pathInfo)
    {
        $locale = $this->routeStrategy->matchingLocale($pathInfo, $this->locales);
        if (!isset($locale)) {
            $locale = reset($this->locales);
        }

        return $this->localRouter($locale);
    }
}
