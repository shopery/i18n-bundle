<?php

/**
 * This file is part of shopery/marketplace
 *
 * Copyright (c) 2016 Shopery.com
 */

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\Routing\RouteCollection;

interface RouteCollector
{
    /**
     * @return RouteCollection
     */
    function getRouteCollection();
}
