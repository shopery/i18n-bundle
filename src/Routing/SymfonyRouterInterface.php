<?php

namespace Shopery\Bundle\I18nBundle\Routing;


use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RouterInterface;

interface SymfonyRouterInterface extends RouterInterface, RequestMatcherInterface
{

}
