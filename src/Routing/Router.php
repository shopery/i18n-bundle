<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Shopery\Bundle\I18nBundle\Routing\RouteStrategy\RouteStrategy;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Router implements RouterInterface
{
    private $collector;
    private $pathStrategy;
    private $routerFactory;
    private $translator;
    private $locales;
    private $context;

    /** @var bool */
    private $initialized = false;
    /** @var ConfigCacheInterface */
    private $cache;
    /** @var CachedRouter */
    private $globalRouter = null;
    /** @var CachedRouter[] */
    private $localRouters = null;

    public function __construct(
        RouteCollector $collector,
        RouteStrategy $routeStrategy,
        CachedRouterFactory $routerFactory,
        TranslatorInterface $translator,
        array $locales,
        RequestContext $context = null
    ) {
        $this->collector = $collector;
        $this->pathStrategy = $routeStrategy;
        $this->routerFactory = $routerFactory;
        $this->translator = $translator;
        $this->locales = $locales;
        $this->context = $context ?: new RequestContext();
    }

    public function setRouteCache(ConfigCacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function setContext(RequestContext $context)
    {
        $this->initialize();

        $this->globalRouter()->setContext($context);
        foreach ($this->localRouters() as $router) {
            $router->setContext($context);
        }

        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getRouteCollection()
    {
        $this->initialize();

        $collection = new RouteCollection();
        $collection->addCollection(
            $this->globalRouter()->getRouteCollection()
        );
        foreach ($this->localRouters() as $router) {
            $collection->addCollection($router->getRouteCollection());
        }

        return $collection;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        $this->initialize();

        try {
            $locale = $this->detectLocaleFromParameters($parameters);

            return $this->localRouter($locale)->generate(
                $this->translateRouteName($locale, $name),
                $parameters,
                $referenceType
            );

        } catch (RouteNotFoundException $e) {

            return $this->globalRouter()->generate($name, $parameters, $referenceType);
        }
    }

    public function match($pathInfo)
    {
        $this->initialize();

        foreach ($this->detectLocalesFromPath($pathInfo) as $locale) {
            try {
                return $this->localRouter($locale)->match($pathInfo);
            } catch (ResourceNotFoundException $e) {
                // Fallback
            }
        }

        return $this->globalRouter()->match($pathInfo);
    }

    private function initialize()
    {
        if ($this->initialized) {
            return;
        }

        if (!$this->cache->isFresh()) {
            $collection = $this->collector->getRouteCollection();

            list($global, $local) = $this->splitCollection($collection->all());
            $globalResources = $this->globalRouter()->initialize($global);

            $resources = array_merge($collection->getResources(), $globalResources);
            foreach ($this->localRouters() as $locale => $router) {

                $localeResources = $router->initialize(
                    $this->translateRoutes($local, $locale)
                );

                // TODO: Better resource detection
                $localeResources[] = new FileResource(
                    __DIR__ . '/../../Resources/translations/routes.' . $locale . '.yml'
                );

                $resources = array_merge($resources, $localeResources);
            }

            $this->cache->write(serialize($collection->all()), $resources);
        }

        $this->initialized = true;
    }

    /**
     * @param Route[] $routes
     *
     * @return RouteCollection[]
     */
    private function splitCollection($routes)
    {
        $global = new RouteCollection();
        $local = new RouteCollection();

        foreach ($routes as $name => $route) {
            ($route->getOption('i18n') === false ? $global : $local)->add($name, $route);
        }

        return [ $global, $local ];
    }

    private function globalRouter()
    {
        if (!$this->globalRouter) {
            $globalRouter = $this->routerFactory->create();
            $globalRouter->setContext($this->context);

            $this->globalRouter = $globalRouter;
        }

        return $this->globalRouter;
    }

    private function localRouters()
    {
        if (!$this->localRouters) {
            $localRouters = [];
            foreach ($this->locales as $locale) {
                $router = $this->routerFactory->create($locale);
                $router->setContext($this->context);
                $localRouters[$locale] = $router;
            }

            $this->localRouters = $localRouters;
        }

        return $this->localRouters;
    }

    private function localRouter($locale)
    {
        return $this->localRouters()[$locale];
    }

    private function translateRoutes(RouteCollection $collection, $locale)
    {
        $result = new RouteCollection();
        foreach ($collection as $name => $route) {
            $result->add(
                $this->translateRouteName($locale, $name),
                $this->translateRoute($locale, $name, $route)
            );
        }

        return $result;
    }

    /**
     * @return string
     */
    private function translateRouteName($locale, $name)
    {
        return $locale . '__' . $name;
    }

    private function translateRoute($locale, $name, Route $route)
    {
        $route = clone $route;
        $route->setDefault('_locale', $locale);

        $translatedPath = $this->translator->trans($name, [], 'routes', $locale);
        if ($translatedPath !== $name) {
            $route->setPath($translatedPath);
        }

        $route->setPath(
            $this->pathStrategy->withLocale(
                $route->getPath(),
                $locale,
                $this->locales
            )
        );

        return $route;
    }

    private function detectLocaleFromParameters(array $parameters)
    {
        if (isset($parameters['_locale'])) {
            $locale = $parameters['_locale'];

            if (in_array($locale, $this->locales)) {
                return $locale;
            }
        }

        return reset($this->locales);
    }

    private function detectLocalesFromPath($pathInfo)
    {
        return $this->pathStrategy->matchingLocales($pathInfo, $this->locales);
    }
}
