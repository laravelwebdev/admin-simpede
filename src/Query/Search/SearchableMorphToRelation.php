<?php

namespace Laravel\Nova\Query\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;

class SearchableMorphToRelation extends SearchableRelation
{
    /**
     * Construct a new search.
     *
     * @param  array<int, class-string<\Illuminate\Database\Eloquent\Model|\Laravel\Nova\Resource>|string>  $types
     * @return void
     */
    public function __construct(
        string $relation,
        Expression|string $column,
        public array $types = []
    ) {
        parent::__construct($relation, $column);
    }

    /**
     * Apply the search.
     */
    #[\Override]
    public function __invoke(Builder $query, string $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        return $query->{$whereOperator.'HasMorph'}($this->relation, $this->morphTypes(), function ($query) use ($search, $connectionType) {
            return Column::from($this->column)->__invoke(
                $query, $search, $connectionType, 'where'
            );
        });
    }

    /**
     * Get available morph types.
     *
     * @return array<int, class-string<\Illuminate\Database\Eloquent\Model>|string>|string
     */
    protected function morphTypes(): array|string
    {
        if (empty($this->types)) {
            return '*';
        }

        return collect($this->types)
            ->map(function ($resource) {
                return $resource::$model ?? $resource;
            })->all();
    }
}
