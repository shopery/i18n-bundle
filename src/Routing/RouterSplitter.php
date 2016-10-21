<?php
/**
 * This file is part of shopery/shopery
 *
 * Copyright (c) 2016 Shopery.com
 */

namespace Shopery\Bundle\I18nBundle\Routing;


use Shopery\Bundle\I18nBundle\Routing\RouteStrategy\RouteStrategy;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouterSplitter implements RouterInterface
{
    const LOCALIZED = 'i18n';

    private $globalRouter;
    private $localizedRouter;
    private $routeStrategy;

    /**
     * @param string[] $locales
     */
    public function __construct(
        array $locales,
        RouteStrategy $routeStrategy,
        array $options = array(),
        RequestContext $context = null
    ) {
        $this->globalRouter = new CachedRouter($options, $context);

        $this->localizedRouter = new LocalizedRouter(
            $locales,
            $routeStrategy,
            $options,
            $context
        );

        $this->routeStrategy = $routeStrategy;
    }

    public function initializeRouteCollection(RouteCollection $routes)
    {
        $localized = new RouteCollection();
        $global = new RouteCollection();

        foreach ($routes->all() as $name => $route) {
            if ($this->routeIsGlobal($route)) {
                $global->add($name, $route);
            } else {
                $localized->add($name, $route);
            }
        }

        $this->globalRouter->initializeRouteCollection($global);
        $this->localizedRouter->initializeRouteCollection($localized);
    }

    public function setContext(RequestContext $context)
    {
        $this->globalRouter->setContext($context);
        $this->localizedRouter->setContext($context);
    }

    public function getContext()
    {
        //All contexts are synchronized, so just return one of them.
        return $this->globalRouter->getContext();
    }

    public function getRouteCollection()
    {
        $routes = clone $this->globalRouter->getRouteCollection();
        $routes->addCollection($this->localizedRouter->getRouteCollection());

        return $routes;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (empty($parameters['_locale'])) {
            throw new \InvalidArgumentException(sprintf(
                "'_locale' parameter required in %s to generate a route. Double-check it's added in the upper layers",
                self::class
            ));
        }

        try {
            $this->localizedRouter->generate($name, $parameters, $referenceType);
        } catch (RouteNotFoundException $e) {
            $this->globalRouter->generate($name, $parameters, $referenceType);
        }
    }

    public function match($pathinfo)
    {
        if ($this->routeStrategy->pathMustBeLocalized($pathinfo)) {
            return $this->localizedRouter->match($pathinfo);
        }
        if ($this->routeStrategy->pathMustBeGlobal($pathinfo)) {
            return $this->globalRouter->match($pathinfo);
        }

        try {
            return $this->globalRouter->match($pathinfo);
        } catch (RouteNotFoundException $e) {
            return $this->localizedRouter->match($pathinfo);
        }
    }

    /**
     * @return bool
     */
    private function routeIsGlobal(Route $route)
    {
        return $route->hasOption(self::LOCALIZED) && $route->getOption(self::LOCALIZED) === false;
    }
}
