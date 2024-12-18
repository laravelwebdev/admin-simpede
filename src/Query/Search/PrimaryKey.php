<?php

namespace Laravel\Nova\Query\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;

class PrimaryKey extends Column
{
    /**
     * Construct a new search.
     *
     * @return void
     */
    public function __construct(
        Expression|string $column,
        public int $maxPrimaryKeySize = PHP_INT_MAX
    ) {
        parent::__construct($column);
    }

    /**
     * Apply the search.
     */
    #[\Override]
    public function __invoke(Builder $query, string|int $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        $model = $query->getModel();

        $canSearchPrimaryKey = ctype_digit($search) &&
                               in_array($model->getKeyType(), ['int', 'integer']) &&
                               ($connectionType != 'pgsql' || $search <= $this->maxPrimaryKeySize);

        if (! $canSearchPrimaryKey) {
            return parent::__invoke($query, $search, $connectionType, $whereOperator);
        }

        return $query->{$whereOperator}($model->getQualifiedKeyName(), $search);
    }
}
