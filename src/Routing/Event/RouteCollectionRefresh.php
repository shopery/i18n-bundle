<?php

namespace Shopery\Bundle\I18nBundle\Routing\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteCollectionRefresh extends Event
{
    const EVENT_NAME = 'route_collection_refresh';

    private $collection;

    public function __construct(RouteCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return Route[]|\Iterator
     */
    public function routes()
    {
        return $this->collection->getIterator();
    }

    public function addResources(array $resources)
    {
        foreach ($resources as $resource) {
            $this->collection->addResource($resource);
        }
    }
}
