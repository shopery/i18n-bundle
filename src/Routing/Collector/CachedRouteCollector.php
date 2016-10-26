<?php

namespace Shopery\Bundle\I18nBundle\Routing\Collector;

use Shopery\Bundle\I18nBundle\Routing\CachedRouterFactory;
use Shopery\Bundle\I18nBundle\Routing\RouteCollector;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\DirectoryResource;

class CachedRouteCollector implements RouteCollector
{
    private $inner;
    private $cacheFactory;
    private $routerFactory;
    private $cacheDir;
    private $pathName;

    private $collection;

    public function __construct(
        RouteCollector $inner,
        ConfigCacheFactoryInterface $cacheFactory,
        CachedRouterFactory $routerFactory,
        $cacheDir,
        $pathName
    ) {
        $this->inner = $inner;
        $this->cacheFactory = $cacheFactory;
        $this->routerFactory = $routerFactory;
        $this->cacheDir = $cacheDir;
        $this->pathName = $pathName;
    }

    public function warmUp($cacheDir)
    {
        $oldRouterCacheDir = $this->routerFactory->setCacheDir($cacheDir);
        $oldCacheDir = $this->setCacheDir($cacheDir);

        $this->cache();

        $this->setCacheDir($oldCacheDir);
        $this->routerFactory->setCacheDir($oldRouterCacheDir);
    }

    public function getRouteCollection()
    {
        if (!$this->collection) {
            $cache = $this->cache();
            $this->collection = unserialize(
                file_get_contents($cache->getPath())
            );
        }

        return $this->collection;
    }

    public function cache()
    {
        $filename = $this->cacheDir . '/' . $this->pathName;
        $callable = function (ConfigCacheInterface $cache) {
            $this->collection = $collection = $this->inner->getRouteCollection();
            $resources = $collection->getResources();
            $resources[] = new DirectoryResource(__DIR__ . '/../..');

            $cache->write(serialize($collection), $resources);
        };

        return $this->cacheFactory->cache($filename, $callable);
    }

    private function setCacheDir($cacheDir)
    {
        $oldCacheDir = $this->cacheDir;
        $this->cacheDir = $cacheDir;

        return $oldCacheDir;
    }
}
