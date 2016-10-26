<?php

namespace Shopery\Bundle\I18nBundle\CacheWarmer;

use Shopery\Bundle\I18nBundle\Routing\Collector\CachedRouteCollector;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class RoutingCacheWarmer implements CacheWarmerInterface
{
    private $collector;

    public function __construct(CachedRouteCollector $collector)
    {
        $this->collector = $collector;
    }

    public function isOptional()
    {
        return false;
    }

    public function warmUp($cacheDir)
    {
        $this->collector->warmUp($cacheDir);
    }
}
