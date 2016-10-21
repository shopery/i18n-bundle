<?php

namespace Shopery\Bundle\I18nBundle\Routing\Collector;

use Shopery\Bundle\I18nBundle\Routing\RouteCollector;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\ConfigCacheFactory;

class CachedRouteCollector implements RouteCollector
{
    private $inner;
    private $filename;
    /** @var ConfigCacheFactory */
    private $cacheFactory;
    private $collection;

    public function __construct(RouteCollector $inner, $filename)
    {
        $this->inner = $inner;
        $this->filename = $filename;
    }

    public function getRouteCollection()
    {
        if (!$this->collection) {
            $this->collection = $this->loadFromCache();
        }

        return $this->collection;
    }

    public function setCacheConfigFactory(ConfigCacheFactory $configCacheFactory)
    {
        $this->cacheFactory = $configCacheFactory;
    }

    private function loadFromCache()
    {
        if (!$this->cacheFactory) {
            return $this->getFreshRouteCollection();
        }

        $cache = $this->cacheFactory->cache(
            $this->filename,
            function (ConfigCache $cache) {
                $collection = $this->getFreshRouteCollection();
                $cache->write(serialize($collection), $collection->getResources());
            }
        );

        return unserialize($cache->getPath());
    }

    private function getFreshRouteCollection()
    {
        return $this->inner->getRouteCollection();
    }
}
