<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

use Shopery\Bundle\I18nBundle\Routing\Collector\CachedRouteCollector;

class DebugRouter implements RouterInterface
{
    private $inner;
    private $collector;
    private $initialized = false;

    public function __construct(
        RouterInterface $inner,
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

    private function initialize()
    {
        if (!$this->initialized) {
            $this->collector->cache();
            $this->initialized = true;
        }

        return $this;
    }
}
