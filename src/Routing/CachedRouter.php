<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CachedRouter implements RouterInterface
{
    private $options;
    private $context;
    private $locale;

    /**
     * @param string|null $locale
     */
    public function __construct(array $options = array(), RequestContext $context = null, $locale = null)
    {
        $this->options = $options;
        $this->context = $context;
        $this->locale = $locale;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        try {
            return parent::generate($name, $parameters, $referenceType);
        } catch (RouteNotFoundException $e) {
            return parent::generate($this->locale . '__' . $name, $parameters, $referenceType);
        }
    }

    public function initializeRouteCollection(RouteCollection $collection)
    {
        $this->collection = new RouteCollection();

        foreach ($collection->all() as $name => $route) {
            $route = clone $route;
            $route->setDefault('_locale', $this->locale);

            $translatedPath = $translator->trans($name, [], 'routes', $this->locale);
            if ($translatedPath !== $name) {
                $route->setPath($translatedPath);
            }

            $name = $this->locale . '__' . $name;
            $route->setPath($pathTransformer($route->getPath(), $this->locale));

            $this->collection->add($name, $route);
        }

        $this->collection->addResource(new FileResource(__FILE__));
        foreach ($collection->getResources() as $resource) {
            $this->collection->addResource($resource);
        }

        $this->warmUp($this->options['cache_dir']);
    }

    public function setContext(RequestContext $context)
    {
        // TODO: Implement setContext() method.
    }

    public function getContext()
    {
        // TODO: Implement getContext() method.
    }

    public function getRouteCollection()
    {
        // TODO: Implement getRouteCollection() method.
    }

    public function match($pathinfo)
    {
        // TODO: Implement match() method.
    }
}
