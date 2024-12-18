<?php

namespace Laravel\Nova\Http\Requests;

use Laravel\Nova\Contracts\QueryBuilder;

class ResourceIndexRequest extends NovaRequest
{
    use CountsResources;
    use QueriesResources;

    /**
     * Get the paginator instance for the index request.
     */
    public function searchIndex(): array
    {
        return app()->make(QueryBuilder::class, [$this->resource()])->search(
            $this, $this->newQuery(), $this->search,
            $this->filters()->all(), $this->orderings(), $this->trashed()
        )->paginate((int) $this->perPage());
    }

    /**
     * Get the count of the resources.
     */
    public function toCount(): int
    {
        return app()->make(QueryBuilder::class, [$this->resource()])->search(
            $this, $this->newQuery(), $this->search,
            $this->filters()->all(), $this->orderings(), $this->trashed()
        )->toBaseQueryBuilder()->getCountForPagination();
    }

    /**
     * Get per page.
     */
    public function perPage(): int
    {
        $resource = $this->resource();

        if ($this->viaRelationship()) {
            return (int) $resource::$perPageViaRelationship;
        }

        $perPageOptions = $resource::perPageOptions();

        if (empty($perPageOptions)) {
            $perPageOptions = [$resource::newModel()->getPerPage()];
        }

        return (int) in_array($this->perPage, $perPageOptions) ? $this->perPage : $perPageOptions[0];
    }
}
