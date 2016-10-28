<?php

namespace Shopery\Bundle\I18nBundle\Routing\Collector;

use Shopery\Bundle\I18nBundle\Routing\RouteCollector;
use Symfony\Component\Config\Loader\LoaderInterface;

class ResourceRouteCollector implements RouteCollector
{
    private $loader;
    private $resource;
    private $type;

    public function __construct(LoaderInterface $loader, $resource, $type)
    {
        $this->loader = $loader;
        $this->resource = $resource;
        $this->type = $type;
    }

    public function getRouteCollection()
    {
        return $this->loader->load($this->resource, $this->type);
    }
}
