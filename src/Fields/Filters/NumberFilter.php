<?php

namespace Laravel\Nova\Fields\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Http\Requests\NovaRequest;

class NumberFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'number-field';

    /**
     * Apply the filter to the given query.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, Builder $query, mixed $value)
    {
        $value = collect($value)->transform(function ($value) {
            return ! $this->field->isValidNullValue($value) ? $value : null;
        });

        if ($value->filter()->isNotEmpty()) {
            $this->field->applyFilter($request, $query, $value->all());
        }

        return $query;
    }

    /**
     * Get the default options for the filter.
     *
     * @return array|mixed
     */
    public function default()
    {
        return [null, null];
    }
}
