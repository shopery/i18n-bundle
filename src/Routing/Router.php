<?php
/**
 * This file is part of shopery/shopery
 *
 * Copyright (c) 2016 Shopery.com
 */

namespace Shopery\Bundle\I18nBundle\Routing;


use Shopery\Bundle\I18nBundle\Routing\Event\RouteCollectionRefresh;
use Shopery\Bundle\I18nBundle\Routing\RouteStrategy\RouteStrategy;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class Router implements RouterInterface
{
    const LOCALIZED = 'i18n';

    private $globalRouter;
    private $localizedRouter;
    private $routeStrategy;
    private $collector;
    private $cachedRouterFactory;

    public function __construct(
        LocalizedRouter $localizedRouter,
        RouteCollector $collector,
        RouteStrategy $routeStrategy,
        CachedRouterFactory $cachedRouterFactory,
        RequestContext $context = null
    ) {
        $this->globalRouter = $cachedRouterFactory->create(null);
        $this->localizedRouter = $localizedRouter;
        $this->routeStrategy = $routeStrategy;
        $this->collector = $collector;
        $this->cachedRouterFactory = $cachedRouterFactory;
        $this->setContext($context ?: new RequestContext());
    }

    public function initializeRouteCollection(RouteCollectionRefresh $event)
    {
        $localized = new RouteCollection();
        $global = new RouteCollection();

        foreach ($event->routes() as $name => $route) {
            if ($this->routeIsGlobal($route)) {
                $global->add($name, $route);
            } else {
                $localized->add($name, $route);
            }
        }

        $event->addResources(
            $this->globalRouter->initializeRouteCollection($global)
        );

        $event->addResources(
            $this->localizedRouter->initializeRouteCollection($localized)
        );
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
        $this->collector->getRouteCollection();

        $collection = new RouteCollection();
        $collection->addCollection($this->globalRouter->getRouteCollection());
        $collection->addCollection($this->localizedRouter->getRouteCollection());

        return $collection;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        $this->collector->getRouteCollection();

        try {
            return $this->localizedRouter->generate($name, $parameters, $referenceType);
        } catch (RouteNotFoundException $e) {
            return $this->globalRouter->generate($name, $parameters, $referenceType);
        }
    }

    public function match($pathInfo)
    {
        $this->collector->getRouteCollection();

        if ($this->routeStrategy->pathMustBeLocalized($pathInfo)) {
            return $this->localizedRouter->match($pathInfo);
        }

        if ($this->routeStrategy->pathMustBeGlobal($pathInfo)) {
            return $this->globalRouter->match($pathInfo);
        }

        try {
            return $this->globalRouter->match($pathInfo);
        } catch (ResourceNotFoundException $e) {
            return $this->localizedRouter->match($pathInfo);
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
