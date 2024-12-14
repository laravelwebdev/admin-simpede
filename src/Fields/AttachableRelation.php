<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Str;
use Laravel\Nova\Contracts\QueryBuilder;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Laravel\Nova\TrashedStatus;

trait AttachableRelation
{
    /**
     * Determines if the display values should be automatically sorted.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool
     */
    public $reordersOnAttachableCallback = true;

    /**
     * Build an attachable query for the field.
     */
    public function buildAttachableQuery(NovaRequest $request, bool $withTrashed = false): QueryBuilder
    {
        $model = forward_static_call([$resourceClass = $this->resourceClass, 'newModel']);

        $query = app()->make(QueryBuilder::class, [$resourceClass]);

        $request->first === 'true'
                        ? $query->whereKey($model->newQueryWithoutScopes(), $request->current)
                        : $query->search(
                            $request, $model->newQuery(), $request->search,
                            [], [], TrashedStatus::fromBoolean($withTrashed)
                        );

        return $query->tap(function ($query) use ($request, $model) {
            forward_static_call($this->attachableQueryCallable($request, $model), $request, $query, $this);
        });
    }

    /**
     * Get the attachable query method name.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function attachableQueryCallable(NovaRequest $request, $model): callable
    {
        return ($method = $this->attachableQueryMethod($request, $model))
                    ? [$request->resource(), $method]
                    : [$this->resourceClass, 'relatableQuery'];
    }

    /**
     * Get the attachable query method name.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function attachableQueryMethod(NovaRequest $request, $model): ?string
    {
        $method = 'relatable'.Str::plural(class_basename($model));

        return method_exists($request->resource(), $method) ? $method : null;
    }

    /**
     * Format the given attachable resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model  $resource
     */
    public function formatAttachableResource(NovaRequest $request, $resource): array
    {
        if (! $resource instanceof Resource) {
            $resource = Nova::newResourceFromModel($resource);
        }

        return array_filter([
            'avatar' => $resource->resolveAvatarUrl($request),
            'display' => $this->formatDisplayValue($resource),
            'value' => optional(ID::forResource($resource))->value ?? $resource->getKey(),
            'subtitle' => $resource->subtitle(),
        ]);
    }

    /**
     * Determine if the display values should be automatically sorted when rendering attachable relation.
     */
    public function shouldReorderAttachableValues(NovaRequest $request): bool
    {
        if (is_callable($this->reordersOnAttachableCallback)) {
            return call_user_func($this->reordersOnAttachableCallback, $request);
        }

        return $this->reordersOnAttachableCallback;
    }

    /**
     * Determine reordering on attachables.
     *
     * @return $this
     */
    public function dontReorderAttachables()
    {
        $this->reordersOnAttachableCallback = false;

        return $this;
    }

    /**
     * Determine reordering on attachables.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $value
     * @return $this
     */
    public function reorderAttachables(callable|bool $value = true)
    {
        $this->reordersOnAttachableCallback = $value;

        return $this;
    }
}
