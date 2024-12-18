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
     * @return static<TKey, TValue>
     */
    public function authorizedToSeeOnIndex(NovaRequest $request)
    {
        return $this->filter->shownOnIndex()
            ->filter(function ($action) use ($request) {
                if ($action->sole === true) {
                    return ! $request->allResourcesSelected()
                        && $request->selectedResourceIds()->count() <= 1
                        && $action->authorizedToSee($request);
                }

                return $action->authorizedToSee($request);
            });
    }

    /**
     * Get the actions that are authorized for viewing on detail pages.
     *
     * @return static<TKey, TValue>
     */
    public function authorizedToSeeOnDetail(NovaRequest $request)
    {
        return $this->filter->shownOnDetail()
            ->filter->authorizedToSee($request);
    }

    /**
     * Get the actions that are authorized for viewing on table rows.
     *
     * @return static<TKey, TValue>
     */
    public function authorizedToSeeOnTableRow(NovaRequest $request)
    {
        return $this->filter->shownOnTableRow()
            ->filter->authorizedToSee($request);
    }

    /**
     * Determine whether the actions available for display can be executed.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return $this
     */
    public function withAuthorizedToRun(NovaRequest $request, $model)
    {
        return $this->each(
            fn (Action $action) => $action->authorizedToRun($request, $model)
        );
    }

    /**
     * Return action counts by type on index.
     *
     * @return array{sole: int, standalone: int, resource: int}
     */
    public function countsByTypeOnIndex(): array
    {
        [$standalone, $actions] = $this->filter->shownOnIndex()
            ->partition->isStandalone();

        [$sole, $resource] = $actions->partition(
            fn ($action) => $action->sole === true
        );

        return [
            'sole' => $sole->count(),
            'standalone' => $standalone->count(),
            'resource' => $resource->count(),
        ];
    }
}
