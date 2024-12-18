<?php

namespace Laravel\Nova\Query\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;

class SearchableText extends Column
{
    /**
     * Apply the search.
     */
    #[\Override]
    public function __invoke(Builder $query, string $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        if (in_array($connectionType, ['mysql', 'pgsql'])) {
            $query->{$whereOperator.'FullText'}(
                $this->columnName($query), $search
            );
        }

        return $query;
    }
}
