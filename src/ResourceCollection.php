<?php

namespace Laravel\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class ResourceCollection extends Collection
{
    /**
     * Return the authorized resources of the collection.
     *
     * @return static<TKey, TValue>
     */
    public function authorized(Request $request)
    {
        return $this->filter(
            fn ($resource) => $resource::authorizedToViewAny($request)
        );
    }

    /**
     * Return the resources available to be displayed in the navigation.
     *
     * @return static<TKey, TValue>
     */
    public function availableForNavigation(Request $request)
    {
        return $this->filter(
            fn ($resource) => $resource::availableForNavigation($request)
        );
    }

    /**
     * Return the searchable resources for the collection.
     *
     * @return static<TKey, TValue>
     */
    public function searchable()
    {
        return $this->filter(
            fn ($resource) => $resource::$globallySearchable
        );
    }

    /**
     * Sort the resources by their group property.
     *
     * @return \Illuminate\Support\Collection<string, \Laravel\Nova\ResourceCollection<array-key, TValue>>
     */
    public function grouped()
    {
        return $this->groupBy(
            fn ($resource, $key) => (string) $resource::group()
        )->toBase()->sortKeys();
    }

    /**
     * Group the resources for display in navigation.
     *
     * @return \Illuminate\Support\Collection<string, \Laravel\Nova\ResourceCollection<array-key, TValue>>
     */
    public function groupedForNavigation(Request $request)
    {
        return $this->availableForNavigation($request)->grouped();
    }
}
