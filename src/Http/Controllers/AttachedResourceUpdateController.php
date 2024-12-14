<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Nova\Actions\ActionEvent;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Throwable;

class AttachedResourceUpdateController extends Controller
{
    use HandlesCustomRelationKeys;

    /**
     * The action event for the action.
     */
    protected ?ActionEvent $actionEvent = null;

    /**
     * Update an attached resource pivot record.
     *
     * @throws \Throwable
     */
    public function __invoke(NovaRequest $request): mixed
    {
        $resource = $request->resource();

        $model = $request->findModelOrFail();

        tap(new $resource($model), function ($resource) use ($request) {
            abort_unless($resource->hasRelatableField($request, $request->viaRelationship), 404);
        });

        $this->validate($request, $model, $resource);

        try {
            return DB::connection($model->getConnectionName())->transaction(function () use ($request, $resource, $model) {
                $model->setRelation(
                    $model->{$request->viaRelationship}()->getPivotAccessor(),
                    $pivot = $this->findPivot($request, $model)
                );

                if ($this->modelHasBeenUpdatedSinceRetrieval($request, $pivot)) {
                    return response('', 409);
                }

                [$pivot, $callbacks] = $resource::fillPivotForUpdate($request, $model, $pivot);

                DB::transaction(function () use ($request, $model, $pivot) {
                    Nova::usingActionEvent(function (ActionEvent $actionEvent) use ($request, $model, $pivot) {
                        $this->actionEvent = $actionEvent->forAttachedResourceUpdate($request, $model, $pivot);
                        $this->actionEvent->save();
                    });
                });

                $pivot->save();

                collect($callbacks)->each->__invoke();
            });
        } catch (Throwable $e) {
            optional($this->actionEvent)->delete();
            throw $e;
        }
    }

    /**
     * Validate the attachment request.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     */
    protected function validate(NovaRequest $request, $model, string $resourceClass): void
    {
        tap($this->updateRulesFor($request, $resourceClass), function ($rules) use ($resourceClass, $request) {
            $attribute = $resourceClass::validationAttachableAttributeFor($request, $request->relatedResource);

            Validator::make($request->all(), $rules, [], $this->customRulesKeys($request, $attribute))->validate();

            $resourceClass::validateForAttachmentUpdate($request);
        });
    }

    /**
     * Get update rules for request from the resource.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     */
    protected function updateRulesFor(NovaRequest $request, string $resourceClass): array
    {
        $rules = $resourceClass::updateRulesFor($request, $this->getRuleKey($request));

        if ($this->usingCustomRelationKey($request)) {
            $rules[$request->relatedResource] = $rules[$request->viaRelationship];
            unset($rules[$request->viaRelationship]);
        }

        return $rules;
    }

    /**
     * Find the pivot model for the operation.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function findPivot(NovaRequest $request, $model): Pivot
    {
        $relation = $model->{$request->viaRelationship}();

        if ($request->viaPivotId) {
            tap($relation->getPivotClass(), function ($pivotClass) use ($relation, $request) {
                $relation->wherePivot((new $pivotClass)->getKeyName(), $request->viaPivotId);
            });
        }

        $accessor = $relation->getPivotAccessor();

        return $relation
            ->withoutGlobalScopes()
            ->lockForUpdate()
            ->findOrFail($request->relatedResourceId)->{$accessor};
    }

    /**
     * Determine if the model has been updated since it was retrieved.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function modelHasBeenUpdatedSinceRetrieval(NovaRequest $request, $model): bool
    {
        $column = $model->getUpdatedAtColumn();

        if (! ($model->usesTimestamps() && $model->{$column})) {
            return false;
        }

        return $request->input('_retrieved_at') && $model->{$column}->gt(
            Carbon::createFromTimestamp($request->input('_retrieved_at'))
        );
    }
}
