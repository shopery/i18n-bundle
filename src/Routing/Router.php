<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Shopery\Bundle\I18nBundle\Routing\RouteStrategy\RouteStrategy;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class Router implements RouterInterface
{
    private $locales;
    private $factory;
    private $routeStrategy;
    private $context;

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
        $router->setContext($this->context);

        return $router->generate($name, $parameters, $referenceType);
    }

    public function match($pathInfo)
    {
        $locale = $this->routeStrategy->matchingLocale($pathInfo, $this->locales);
        if (!isset($locale)) {
            $locale = reset($this->locales);
        }

        $router = $this->localRouter($locale);
        $router->setContext($this->context);

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
}
