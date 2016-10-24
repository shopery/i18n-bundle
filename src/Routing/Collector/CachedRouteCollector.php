<?php

namespace Shopery\Bundle\I18nBundle\Routing\Collector;

use Shopery\Bundle\I18nBundle\Routing\Event\RouteCollectionRefresh;
use Shopery\Bundle\I18nBundle\Routing\RouteCollector;

use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouteCollection;

class CachedRouteCollector implements RouteCollector
{
    private $inner;
    private $dispatcher;
    private $filename;
    private $cacheFactory;
    private $collection;

    public function __construct(
        RouteCollector $inner,
        EventDispatcherInterface $dispatcher,
        ConfigCacheFactoryInterface $cacheFactory,
        $filename
    ) {
        $this->inner = $inner;
        $this->dispatcher = $dispatcher;
        $this->cacheFactory = $cacheFactory;
        $this->filename = $filename;
    }

    public function getRouteCollection()
    {
        if (!$this->collection) {
            $this->collection = $this->loadFromCache();
        }

        return $this->collection;
    }

    private function loadFromCache()
    {
        if (!$this->cacheFactory) {
            return $this->getFreshRouteCollection();
        }

        $cache = $this->cacheFactory->cache(
            $this->filename,
            function (ConfigCacheInterface $cache) {
                $collection = $this->getFreshRouteCollection();
                $this->dispatchRefreshRouteCollection($collection);
                $cache->write(serialize($collection), $collection->getResources());
            }
        );

        return unserialize(file_get_contents($cache->getPath()));
    }

    private function getFreshRouteCollection()
    {
        return $this->inner->getRouteCollection();
    }

    private function dispatchRefreshRouteCollection(RouteCollection $collection)
    {
        $this->dispatcher->dispatch(
            RouteCollectionRefresh::EVENT_NAME,
            new RouteCollectionRefresh($collection)
        );
    }
}
