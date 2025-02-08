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
        /** @phpstan-ignore return.type */
        return $this->filter(
            static fn ($resource) => $resource::authorizedToViewAny($request)
        );
    }

    /**
     * Return the resources available to be displayed in the navigation.
     *
     * @return static<TKey, TValue>
     */
    public function availableForNavigation(Request $request)
    {
        /** @phpstan-ignore return.type */
        return $this->filter(
            static fn ($resource) => $resource::availableForNavigation($request)
        );
    }

    /**
     * Return the searchable resources for the collection.
     *
     * @return static<TKey, TValue>
     */
    public function searchable()
    {
        /** @phpstan-ignore return.type */
        return $this->filter(
            static fn ($resource) => $resource::$globallySearchable
        );
    }

    /**
     * Sort the resources by their group property.
     *
     * @return \Illuminate\Support\Collection<string, \Laravel\Nova\ResourceCollection<array-key, TValue>>
     */
    public function grouped()
    {
        /** @phpstan-ignore return.type */
        return $this->groupBy(
            static fn ($resource, $key) => (string) $resource::group()
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
