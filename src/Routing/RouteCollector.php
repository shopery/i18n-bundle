<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\Routing\RouteCollection;

interface RouteCollector
{
    /**
     * @return RouteCollection
     */
    function getRouteCollection();
}
