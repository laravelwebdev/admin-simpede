<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\NovaRequest;

trait SupportsRelatableQuery
{
    /**
     * Get the relatableQuery callable.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    protected function relatableQueryCallable(NovaRequest $request, $resourceClass, $model)
    {
        return ($method = $this->relatableQueryMethod($request, $model))
            ? [$request->resource(), $method]
            : [$resourceClass, 'relatableQuery'];
    }

    /**
     * Get the relatableQuery method name.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string|null
     */
    protected function relatableQueryMethod(NovaRequest $request, $model)
    {
        $method = 'relatable'.Str::plural(class_basename($model));

        if (method_exists($request->resource(), $method)) {
            return $method;
        }
    }
}
