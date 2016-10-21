<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class Router implements RouterInterface
{
    private $collector;
    private $context;

    public function __construct(RouteCollector $collector, RequestContext $context = null)
    {
        $this->collector = $collector;
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
        return $this->collector->getRouteCollection();
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
    }

    public function match($pathinfo)
    {
    }
}
