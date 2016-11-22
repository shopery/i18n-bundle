<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class CachedRouter implements SymfonyRouterInterface
{
    private $generator;
    private $matcher;
    private $routingFilename;
    private $context;
    /** @var RouteCollection */
    private $routeCollection;

    public function __construct(
        UrlGeneratorInterface $generator,
        UrlMatcherInterface $matcher,
        $routingFilename,
        RequestContext $context = null
    ) {
        $this->generator = $generator;
        $this->matcher = $matcher;
        $this->routingFilename = $routingFilename;
        $this->context = $context;
    }

    public function setContext(RequestContext $context)
    {
        $this->generator->setContext($context);
        $this->matcher->setContext($context);
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return RouteCollection
     */
    public function getRouteCollection()
    {
        //As it's a heavy file and mostly not used, read it only when requested
        if (!isset($this->routeCollection)) {
            $this->routeCollection = unserialize(
                file_get_contents($this->routingFilename)
            );
        }

        return $this->routeCollection;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->generator->generate($name, $parameters, $referenceType);
    }

    public function match($pathinfo)
    {
        return $this->matcher->match($pathinfo);
    }

    public function matchRequest(Request $request)
    {
        if (!$this->matcher instanceof RequestMatcherInterface) {
            // fallback to the default UrlMatcherInterface
            return $this->matcher->match($request->getPathInfo());
        }

        return $this->matcher->matchRequest($request);
    }
}
