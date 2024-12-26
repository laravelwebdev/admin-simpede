<?php

namespace Laravel\Nova\Actions;

use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @template TKey of array-key
 * @template TValue of \Laravel\Nova\Actions\Action
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class ActionCollection extends Collection
{
    /**
     * Get the actions that are authorized for viewing on the index.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return static<TKey, TValue>
     */
    public function authorizedToSeeOnIndex(NovaRequest $request)
    {
        return $this->filter->shownOnIndex()
            ->filter(function ($action) use ($request) {
                if ($action->sole === true) {
                    return ! $request->allResourcesSelected()
                        && $request->selectedResourceIds()->count() === 1
                        && $action->authorizedToSee($request);
                }

                return $action->authorizedToSee($request);
            });
    }

    /**
     * Get the actions that are authorized for viewing on detail pages.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return static<TKey, TValue>
     */
    public function authorizedToSeeOnDetail(NovaRequest $request)
    {
        return $this->filter->shownOnDetail()->filter->authorizedToSee($request);
    }

    /**
     * Get the actions that are authorized for viewing on table rows.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return static<TKey, TValue>
     */
    public function authorizedToSeeOnTableRow(NovaRequest $request)
    {
        return $this->filter->shownOnTableRow()->filter->authorizedToSee($request);
    }

    /**
     * Determine whether the actions available for display can be executed.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return $this
     */
    public function withAuthorizedToRun(NovaRequest $request, $model)
    {
        return $this->each(function (Action $action) use ($request, $model) {
            $action->authorizedToRun($request, $model);
        });
    }

    /**
     * Return action counts by type on index.
     *
     * @return array{standalone: mixed, resource: mixed}
     */
    public function countsByTypeOnIndex()
    {
        [$standalone, $resource] = $this->filter->shownOnIndex()->partition->isStandalone();

        return [
            'standalone' => $standalone->count(),
            'resource' => $resource->count(),
        ];
    }
}
