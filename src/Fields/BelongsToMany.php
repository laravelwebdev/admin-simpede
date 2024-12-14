<?php

namespace Laravel\Nova\Fields;

use Illuminate\Http\Request;
use Laravel\Nova\Contracts\Deletable as DeletableContract;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Contracts\ListableField;
use Laravel\Nova\Contracts\PivotableField;
use Laravel\Nova\Contracts\QueryBuilder;
use Laravel\Nova\Fields\Filters\EloquentFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Laravel\Nova\Rules\RelatableAttachment;
use Laravel\Nova\TrashedStatus;
use Stringable;

/**
 * @method static static make(mixed $name, string|null $attribute = null, string|null $resource = null)
 */
class BelongsToMany extends Field implements DeletableContract, FilterableField, ListableField, PivotableField
{
    use AttachableRelation;
    use Collapsable;
    use Deletable;
    use DetachesPivotModels;
    use DeterminesIfCreateRelationCanBeShown;
    use EloquentFilterable;
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
    public $component = 'belongs-to-many-field';

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
     * The name of the Eloquent "belongs to many" relationship.
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
        $this->manyToManyRelationship = $this->attribute = $attribute ?? ResourceRelationshipGuesser::guessRelation($name);
        $this->deleteCallback = $this->detachmentCallback();

        $this->fieldsCallback = fn () => [];
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
        return 'belongsToMany';
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
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    #[\Override]
    public function resolve($resource, ?string $attribute = null): void
    {
        //
    }

    /**
     * Get the validation rules for this field.
     */
    #[\Override]
    public function getRules(NovaRequest $request): array
    {
        $query = $this->buildAttachableQuery(
            $request, $request->{$this->attribute.'_trashed'} === 'true'
        )->toBase();

        return array_merge_recursive(parent::getRules($request), [
            $this->attribute => ['required', new RelatableAttachment($request, $query, $this)],
        ]);
    }

    /**
     * Get the creation rules for this field.
     */
    #[\Override]
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
            'subtitle' => $resource->subtitle(),
            'value' => optional(ID::forResource($resource))->value ?? $resource->getKey(),
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
     * Return the validation key for the field.
     */
    public function validationKey(): string
    {
        return $this->attribute != $this->resourceName
            ? $this->resourceName
            : $this->attribute;
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter|null
     */
    protected function makeFilter(NovaRequest $request)
    {
        if ($request->viaRelationship()
            && ($request->relationshipType ?? null) === 'belongsToMany'
            && $this->resourceClass::uriKey() === $request->viaResource
        ) {
            return null;
        }

        return new EloquentFilter($this);
    }

    /**
     * Define filterable attribute.
     *
     * @return string
     */
    protected function filterableAttribute(NovaRequest $request)
    {
        if ($request->viaRelationship()) {
            return $request->model()->getQualifiedKeyName();
        } else {
            return $this->resourceClass::newModel()->getQualifiedKeyName();
        }
    }

    /**
     * Define the default filterable callback.
     *
     * @return callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder, mixed, string):void
     */
    protected function defaultFilterableCallback()
    {
        return function (NovaRequest $request, $query, $value, $attribute) {
            $viaRelationship = $request->viaRelationship() && $request->relationshipType === 'belongsToMany';

            $query->when($viaRelationship, function ($query) use ($value) {
                $query->whereKey($value);
            }, function ($query) use ($request, $attribute, $value) {
                if ($this->resourceClass::uriKey() !== $request->viaResource) {
                    $query->whereHas($this->manyToManyRelationship, function ($query) use ($value) {
                        $query->whereKey($value);
                    });
                } else {
                    $query->whereRelation($this->manyToManyRelationship, $attribute, '=', $value);
                }
            });
        };
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function serializeForFilter(): array
    {
        return transform($this->jsonSerialize(), function ($field) {
            return [
                'debounce' => $field['debounce'],
                'displaysWithTrashed' => $field['displaysWithTrashed'],
                'label' => $this->resourceClass::label(),
                'resourceName' => $field['resourceName'],
                'searchable' => $field['searchable'],
                'withSubtitles' => $field['withSubtitles'],
                'uniqueKey' => $field['uniqueKey'],
            ];
        });
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
    #[\Override]
    public function jsonSerialize(): array
    {
        return with(app(NovaRequest::class), function ($request) {
            return array_merge([
                'collapsable' => $this->collapsable,
                'collapsedByDefault' => $this->collapsedByDefault,
                'belongsToManyRelationship' => $this->manyToManyRelationship,
                'relationshipType' => $this->relationshipType(),
                'debounce' => $this->debounce,
                'relatable' => true,
                'perPage' => $this->resourceClass::$perPageViaRelationship,
                'validationKey' => $this->validationKey(),
                'resourceName' => $this->resourceName,
                'searchable' => $this->isSearchable($request),
                'withSubtitles' => $this->withSubtitles,
                'singularLabel' => $this->singularLabel ?? $this->resourceClass::singularLabel(),
                'showCreateRelationButton' => $this->createRelationShouldBeShown($request),
                'displaysWithTrashed' => $this->displaysWithTrashed,
            ], parent::jsonSerialize());
        });
    }
}
