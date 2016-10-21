<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Shopery\Bundle\I18nBundle\Routing\RouteStrategy\RouteStrategy;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LocalizedRouter implements RouterInterface
{
    private $locales;
    private $routeStrategy;
    private $options;
    private $context;
    /** @var  CachedRouter[] */
    private $inners;

    /**
     * LocalizedRouter constructor.
     * @param string[] $locales
     * @param array $options
     * @param RequestContext|null $context
     */
    public function __construct(
        array $locales,
        RouteStrategy $routeStrategy,
        array $options,
        RequestContext $context = null
    ) {
        $this->routeStrategy = $routeStrategy;
        $this->options = $options;
        $this->context = $context;

        foreach ($this->locales as $locale) {
            $this->inners[$locale] = new CachedRouter($options, $context, $locale);
        }
    }

    public function initializeRouteCollection(RouteCollection $collection)
    {
        foreach ($this->inners as $inner) {
            $inner->initializeRouteCollection(clone $collection);
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
        //TODO:: THIS FUNCTION
        if (empty($parameters['_locale'])) {
            //TODO:: Don't crash, ensure locale is defined, or set current, or set default
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
        //TODO:: THIS FUNCTION
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
}
