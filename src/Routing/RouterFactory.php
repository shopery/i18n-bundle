<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\Routing\RequestContext;

interface RouterFactory
{
    function create(RequestContext $context, $locale = null);
}
