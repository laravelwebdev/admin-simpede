<?php

namespace Laravel\Nova\Query\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use Illuminate\Database\Query\Expression;

class Column
{
    /**
     * Construct a new search.
     *
     * @return void
     */
    public function __construct(public ExpressionContract|string $column)
    {
        //
    }

    /**
     * Create Column instance for raw expression value.
     */
    public static function raw(string $column): static
    {
        return new static(new Expression($column));
    }

    /**
     * Create Column instance from raw expression or fluent string.
     *
     * @param  \Illuminate\Database\Query\Expression|string  $column
     */
    public static function from(ExpressionContract|string $column): static|SearchableJson|SearchableRelation
    {
        if ($column instanceof ExpressionContract) {
            return new static($column);
        }

        if (strpos($column, '->') !== false) {
            return new SearchableJson($column);
        } elseif (strpos($column, '.') !== false) {
            [$relation, $columnName] = explode('.', $column, 2);

            return new SearchableRelation($relation, $columnName);
        }

        return new static($column);
    }

    /**
     * Apply the search.
     */
    public function __invoke(Builder $query, string $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        return $query->{$whereOperator}(
            $this->columnName($query),
            $connectionType == 'pgsql' ? 'ilike' : 'like',
            "%{$search}%"
        );
    }

    /**
     * Get the column name.
     */
    protected function columnName(Builder $query): ExpressionContract|string
    {
        return $this->column instanceof ExpressionContract ? $this->column : $query->qualifyColumn($this->column);
    }
}
