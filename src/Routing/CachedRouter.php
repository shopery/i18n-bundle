<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class CachedRouter implements RouterInterface
{
    private $generator;
    private $matcher;
    private $context;

    public function __construct(
        UrlGeneratorInterface $generator,
        UrlMatcherInterface $matcher,
        RequestContext $context = null
    ) {
        $this->generator = $generator;
        $this->matcher = $matcher;
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

    public function getRouteCollection()
    {
        // Should never be called and will fail miserably
        return null;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->generator->generate($name, $parameters, $referenceType);
    }

    public function match($pathinfo)
    {
        return $this->matcher->match($pathinfo);
    }
}
