<?php

namespace Laravel\Nova\Query\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;

class SearchableRelation extends Column
{
    /**
     * Construct a new search.
     *
     * @return void
     */
    public function __construct(
        public string $relation,
        Expression|string $column
    ) {
        parent::__construct($column);
    }

    /**
     * Apply the search.
     */
    #[\Override]
    public function __invoke(Builder $query, string $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        return $query->{$whereOperator.'Has'}($this->relation, function ($query) use ($search, $connectionType) {
            return Column::from($this->column)->__invoke(
                $query, $search, $connectionType, 'where'
            );
        });
    }
}
