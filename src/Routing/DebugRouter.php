<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;

use Shopery\Bundle\I18nBundle\Routing\Collector\CachedRouteCollector;

class DebugRouter implements SymfonyRouterInterface
{
    private $inner;
    private $collector;
    private $initialized = false;

    public function __construct(
        SymfonyRouterInterface $inner,
        CachedRouteCollector $collector
    ) {
        $this->inner = $inner;
        $this->collector = $collector;
    }

    public function setContext(RequestContext $context)
    {
        $this->initialize();

        $this->inner->setContext($context);
    }

    public function getContext()
    {
        return $this->initialize()->inner->getContext();
    }

    public function getRouteCollection()
    {
        return $this->initialize()->collector->getRouteCollection();
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->initialize()->inner->generate($name, $parameters, $referenceType);
    }

    public function match($pathInfo)
    {
        return $this->initialize()->inner->match($pathInfo);
    }

    public function matchRequest(Request $request)
    {
        return $this->initialize()->inner->matchRequest($request);
    }

    private function initialize()
    {
        if (!$this->initialized) {
            $this->collector->cache();
            $this->initialized = true;
        }

        return $this;
    }
}
