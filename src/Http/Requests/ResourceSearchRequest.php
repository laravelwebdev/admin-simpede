<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Database\Eloquent\Collection;
use Laravel\Nova\Contracts\QueryBuilder;

class ResourceSearchRequest extends NovaRequest
{
    use QueriesResources;

    /**
     * Get the paginator instance for the index request.
     */
    public function searchIndex(): Collection
    {
        $resource = $this->resource();
        $model = $this->model();

        $limit = $resource::usesScout()
            ? $resource::$scoutSearchResults
            : $resource::$relatableSearchResults;

        $query = app()->make(QueryBuilder::class, [$resource]);

        $this->first === 'true'
            ? $query->whereKey($model->newQueryWithoutScopes(), $this->current)
            : $query->search(
                $this, $this->newQuery(), $this->search,
                $this->filters()->all(), $this->orderings(), $this->trashed()
            );

        return $query->take($limit)->get();
    }
}
