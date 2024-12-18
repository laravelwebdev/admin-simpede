<?php

namespace Laravel\Nova\Fields;

use Illuminate\Http\Request;
use Laravel\Nova\Contracts\Deletable as DeletableContract;
use Laravel\Nova\Contracts\ListableField;
use Laravel\Nova\Contracts\PivotableField;
use Laravel\Nova\Contracts\QueryBuilder;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Laravel\Nova\Resource;
use Laravel\Nova\Rules\RelatableAttachment;
use Laravel\Nova\TrashedStatus;
use Stringable;

/**
 * @method static static make(mixed $name, string|null $attribute = null, string|null $resource = null)
 */
class MorphToMany extends Field implements DeletableContract, ListableField, PivotableField
{
    use AttachableRelation;
    use Collapsable;
    use Deletable;
    use DetachesPivotModels;
    use DeterminesIfCreateRelationCanBeShown;
    use FormatsRelatableDisplayValues;
    use ManyToManyCreationRules;
    use Searchable;
    use SupportsRelatableQuery;
    use SupportsWithTrashedRelatables;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'morph-to-many-field';

    /**
     * The class name of the related resource.
     *
     * @var class-string<\Laravel\Nova\Resource>
     */
    public $resourceClass;

    /**
     * The URI key of the related resource.
     *
     * @var string
     */
    public $resourceName;

    /**
     * The name of the Eloquent "morph to many" relationship.
     *
     * @var string
     */
    public $manyToManyRelationship;

    /**
     * The callback that should be used to resolve the pivot fields.
     *
     * @var callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Database\Eloquent\Model):array<int, \Laravel\Nova\Fields\Field>
     */
    public $fieldsCallback;

    /**
     * The callback that should be used to resolve the pivot actions.
     *
     * @var callable(\Laravel\Nova\Http\Requests\NovaRequest):array<int, \Laravel\Nova\Actions\Action>
     */
    public $actionsCallback;

    /**
     * The displayable name that should be used to refer to the pivot class.
     *
     * @var string|null
     */
    public $pivotName = null;

    /**
     * The displayable singular label of the relation.
     *
     * @var \Stringable|string|null
     */
    public $singularLabel = null;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  class-string<\Laravel\Nova\Resource>|null  $resource
     * @return void
     */
    public function __construct($name, ?string $attribute = null, ?string $resource = null)
    {
        parent::__construct($name, $attribute);

        $resource ??= ResourceRelationshipGuesser::guessResource($name);

        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->manyToManyRelationship = $this->attribute;
        $this->deleteCallback = $this->detachmentCallback();

        $this->fieldsCallback = fn ($request, $model) => [];
        $this->actionsCallback = fn () => [];

        $this->noDuplicateRelations();
    }

    /**
     * Get the relationship name.
     */
    public function relationshipName(): string
    {
        return $this->manyToManyRelationship;
    }

    /**
     * Get the relationship type.
     */
    public function relationshipType(): string
    {
        return 'morphToMany';
    }

    /**
     * Determine if the field should be displayed for the given request.
     *
     * @return bool
     */
    #[\Override]
    public function authorize(Request $request)
    {
        return call_user_func(
            [$this->resourceClass, 'authorizedToViewAny'], $request
        ) && parent::authorize($request);
    }

    /**
     * Resolve the field's value.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object  $resource
     */
    #[\Override]
    public function resolve($resource, ?string $attribute = null): void
    {
        //
    }

    /**
     * Get the validation rules for this field.
     */
    public function getRules(NovaRequest $request): array
    {
        $withTrashed = $request->{$this->attribute.'_trashed'} === 'true';

        return array_merge_recursive(parent::getRules($request), [
            $this->attribute => array_filter([
                'required', new RelatableAttachment($request, $this->buildAttachableQuery($request, $withTrashed)->toBase(), $this),
            ]),
        ]);
    }

    /**
     * Get the creation rules for this field.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rule|\Illuminate\Contracts\Validation\Rule|callable>>
     */
    public function getCreationRules(NovaRequest $request): array
    {
        return array_merge_recursive(parent::getCreationRules($request), [
            $this->attribute => array_filter($this->getManyToManyCreationRules($request)),
        ]);
    }

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
            forward_static_call($this->relatableQueryCallable($request, $this->resourceClass, $model), $request, $query, $this);
        });
    }

    /**
     * Format the given attachable resource.
     *
     * @param  mixed  $resource
     */
    public function formatAttachableResource(NovaRequest $request, $resource): array
    {
        return array_filter([
            'avatar' => $resource->resolveAvatarUrl($request),
            'display' => $this->formatDisplayValue($resource),
            'value' => optional(ID::forResource($resource))->value ?? $resource->getKey(),
            'subtitle' => $resource->subtitle(),
        ]);
    }

    /**
     * Specify the callback to be executed to retrieve the pivot fields.
     *
     * @param  callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Database\Eloquent\Model):array<int, \Laravel\Nova\Fields\Field>  $callback
     * @return $this
     */
    public function fields(callable $callback)
    {
        $this->fieldsCallback = $callback;

        return $this;
    }

    /**
     * Specify the callback to be executed to retrieve the pivot actions.
     *
     * @param  callable(\Laravel\Nova\Http\Requests\NovaRequest):array<int, \Laravel\Nova\Actions\Action>  $callback
     * @return $this
     */
    public function actions(callable $callback)
    {
        $this->actionsCallback = $callback;

        return $this;
    }

    /**
     * Set the displayable name that should be used to refer to the pivot class.
     *
     * @return $this
     */
    public function referToPivotAs(?string $pivotName)
    {
        $this->pivotName = $pivotName;

        return $this;
    }

    /**
     * Set the displayable singular label of the resource.
     *
     * @return $this
     */
    public function singularLabel(Stringable|string $singularLabel)
    {
        $this->singularLabel = $singularLabel;

        return $this;
    }

    /**
     * Make current field behaves as panel.
     */
    public function asPanel(): Panel
    {
        return Panel::make($this->name, [$this])
                    ->withMeta([
                        'prefixComponent' => true,
                    ])->withComponent('relationship-panel');
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return with(app(NovaRequest::class), function (NovaRequest $request) {
            return array_merge([
                'collapsable' => $this->collapsable,
                'collapsedByDefault' => $this->collapsedByDefault,
                'debounce' => $this->debounce,
                'relatable' => true,
                'morphToManyRelationship' => $this->manyToManyRelationship,
                'relationshipType' => $this->relationshipType(),
                'perPage' => $this->resourceClass::$perPageViaRelationship,
                'resourceName' => $this->resourceName,
                'searchable' => $this->isSearchable($request),
                'withSubtitles' => $this->withSubtitles,
                'singularLabel' => $this->singularLabel ?? $this->resourceClass::singularLabel(),
                'showCreateRelationButton' => $this->createRelationShouldBeShown(app(NovaRequest::class)),
                'displaysWithTrashed' => $this->displaysWithTrashed,
            ], parent::jsonSerialize());
        });
    }
}
