<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Shopery\Bundle\I18nBundle\Routing\RouteStrategy\RouteStrategy;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LocalizedRouter implements RouterInterface
{
    private $routeStrategy;
    private $translator;
    /** @var  CachedRouter[] */
    private $inners = [];

    public function __construct(
        RouteStrategy $routeStrategy,
        CachedRouterFactory $cachedRouterFactory,
        TranslatorInterface $translator
    ) {
        $this->routeStrategy = $routeStrategy;
        $this->translator = $translator;

        foreach ($routeStrategy->allLocales() as $locale) {
            $this->inners[$locale] = $cachedRouterFactory->create($locale);
        }
    }

    public function initializeRouteCollection(RouteCollection $collection)
    {
        $resources = [];
        foreach ($this->inners as $locale => $inner) {

            $innerCollection = new RouteCollection();

            foreach ($collection as $name => $route) {
                $route = clone $route;
                $route->setDefault('_locale', $locale);

                $translatedPath = $this->translator->trans($name, [], 'routes', $locale);
                if ($translatedPath !== $name) {
                    $route->setPath($translatedPath);
                }

                $name = $this->localizedRouteName($locale, $name);

                $route->setPath(
                    $this->routeStrategy->pathWithLocale(
                        $route->getPath(),
                        $locale
                    )
                );

                $innerCollection->add($name, $route);
            }

            $innerResources = $inner->initializeRouteCollection($innerCollection);
            /// TODO: Extract resources from Translator -> MessageCatalogue
            $innerResources[] = new FileResource(
                __DIR__ . '/../../Resources/translations/routes.' . $locale . '.yml'
            );

            $resources = array_merge($resources, $innerResources);
        }

        return $resources;
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
            : $this->contextLocale();

        return $this->inners[$locale]->generate(
            $this->localizedRouteName($locale, $name),
            $parameters,
            $referenceType
        );
    }

    public function match($pathinfo)
    {
        $lastError = null;
        foreach ($this->routeStrategy->localesWhichMayMatchPath($pathinfo) as $locale) {
            try {
                return $this->inners[$locale]->match($pathinfo);
            } catch (ResourceNotFoundException $e) {
                $lastError = $e;
            }
        }

        throw $lastError ?: new ResourceNotFoundException(sprintf('No locale can match path: %s', $pathinfo));
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
    private function contextLocale()
    {
        $context = $this->getContext();
        //If not in context, return default(first) one. TODO:: Improve how to get default language
        return $context->getParameter('_locale') ?: $this->routeStrategy->allLocales()[0];
    }

    /**
     * @return string
     */
    private function localizedRouteName($locale, $name)
    {
        return $locale . '__' . $name;
    }
}
