<?php

namespace Laravel\Nova\Fields;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Contracts\QueryBuilder;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Fields\Filters\BelongsToFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\ResourceIndexRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Laravel\Nova\Rules\Relatable;
use Laravel\Nova\TrashedStatus;
use Laravel\Nova\Util;
use Stringable;

/**
 * @method static static make(mixed $name, string|null $attribute = null, string|null $resource = null)
 */
class BelongsTo extends Field implements FilterableField, RelatableField
{
    use AssociatableRelation;
    use DeterminesIfCreateRelationCanBeShown;
    use EloquentFilterable;
    use FormatsRelatableDisplayValues;
    use Peekable;
    use ResolvesReverseRelation;
    use Searchable;
    use SupportsDependentFields;
    use SupportsWithTrashedRelatables;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'belongs-to-field';

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
     * The resolved BelongsTo Resource.
     *
     * @var \Laravel\Nova\Resource|null
     */
    public $belongsToResource = null;

    /**
     * The name of the Eloquent "belongs to" relationship.
     *
     * @var string
     */
    public $belongsToRelationship;

    /**
     * The key of the related Eloquent model.
     *
     * @var string|int|null
     */
    public $belongsToId = null;

    /**
     * Indicates if the related resource can be viewed.
     *
     * @var bool|null
     */
    public $viewable = null;

    /**
     * The callback that should be run when the field is filled.
     *
     * @var callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):void
     */
    public $filledCallback;

    /**
     * The attribute that is the inverse of this relationship.
     *
     * @var string|null
     */
    public $inverse = null;

    /**
     * The displayable singular label of the relation.
     *
     * @var \Stringable|string
     */
    public $singularLabel;

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
        $this->belongsToRelationship = $this->attribute = $attribute ?? ResourceRelationshipGuesser::guessRelation($name);
        $this->singularLabel = $name;
    }

    /**
     * Get the relationship name.
     */
    public function relationshipName(): string
    {
        return $this->belongsToRelationship;
    }

    /**
     * Get the relationship type.
     */
    public function relationshipType(): string
    {
        return 'belongsTo';
    }

    /**
     * Determine if the field should be displayed for the given request.
     *
     * @param  \Illuminate\Http\Request&\Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    #[\Override]
    public function authorize(Request $request)
    {
        return $this->isNotRedundant($request) && parent::authorize($request);
    }

    /**
     * Determine if the field is not redundant.
     *
     * Ex: Is this a "user" belongs to field in a blog post list being shown on the "user" detail page.
     */
    public function isNotRedundant(NovaRequest $request): bool
    {
        return ! $request instanceof ResourceIndexRequest || ! $this->isReverseRelation($request);
    }

    /**
     * Resolve the field's value.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    #[\Override]
    public function resolve($resource, ?string $attribute = null): void
    {
        $value = null;

        if ($resource instanceof Resource || $resource instanceof Model) {
            if ($resource->relationLoaded($this->attribute)) {
                $value = $resource->getRelation($this->attribute);
            } else {
                $value = $resource->{$this->attribute}()->withoutGlobalScopes()->getResults();
            }
        }

        if ($value) {
            $this->belongsToResource = new $this->resourceClass($value);

            $this->belongsToId = Util::safeInt($value->getKey());

            $this->value = $this->formatDisplayValue($this->belongsToResource);

            $this->viewable = ($this->viewable ?? true) && $this->belongsToResource->authorizedToView(app(NovaRequest::class));
        }
    }

    /**
     * Resolve dependent field value.
     */
    public function resolveDependentValue(NovaRequest $request): mixed
    {
        return $this->belongsToId ?? $this->resolveDefaultValue($request);
    }

    /**
     * Define the callback that should be used to resolve the field's value.
     *
     * @return $this
     */
    public function displayUsing(callable $displayCallback)
    {
        return $this->display($displayCallback);
    }

    /**
     * Get the validation rules for this field.
     */
    #[\Override]
    public function getRules(NovaRequest $request): array
    {
        $query = $this->buildAssociatableQuery(
            $request, $request->{$this->attribute.'_trashed'} === 'true'
        )->toBase();

        return array_merge_recursive(parent::getRules($request), [
            $this->attribute => array_filter([
                $this->nullable ? 'nullable' : 'required',
                new Relatable($request, $query, $this),
            ]),
        ]);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    #[\Override]
    public function fill(NovaRequest $request, object $model): void
    {
        $foreignKey = $this->getRelationForeignKeyName($model->{$this->attribute}());

        parent::fillInto($request, $model, $foreignKey);

        if ($model->isDirty($foreignKey)) {
            $model->unsetRelation($this->attribute);
        }

        if (is_callable($this->filledCallback)) {
            call_user_func($this->filledCallback, $request, $model);
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    #[\Override]
    public function fillForAction(NovaRequest $request, object $model): void
    {
        if ($request->exists($this->attribute)) {
            $value = $request[$this->attribute];

            $model->{$this->attribute} = $this->resourceClass::newModel()->query()->find($value);
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    #[\Override]
    protected function fillAttributeFromRequest(NovaRequest $request, string $requestAttribute, object $model, string $attribute): void
    {
        if ($request->exists($requestAttribute)) {
            $value = $request[$requestAttribute];

            $relation = Relation::noConstraints(function () use ($model) {
                return $model->{$this->attribute}();
            });

            if ($this->isValidNullValue($value)) {
                $relation->dissociate();
            } else {
                $relation->associate($relation->getQuery()->withoutGlobalScopes()->find($value));
            }
        }
    }

    /**
     * Build an associatable query for the field.
     */
    public function searchAssociatableQuery(NovaRequest $request, bool $withTrashed = false): QueryBuilder
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = forward_static_call(
            [$resourceClass = $this->resourceClass, 'newModel']
        );

        $query = app()->make(QueryBuilder::class, [$resourceClass]);

        $request->first === 'true'
            ? $query->whereKey($model->newQueryWithoutScopes(), $request->current)
            : $query->search(
                $request, $model->newQuery(), $request->search,
                [], [], TrashedStatus::fromBoolean($withTrashed)
            );

        return $query->tap(function (Builder $query) use ($request, $resourceClass, $model) {
            $this->applyAssociatableCallbacks($query, $request, $resourceClass, $model);
        });
    }

    /**
     * Build an associatable query for the field.
     */
    public function buildAssociatableQuery(NovaRequest $request, bool $withTrashed = false): QueryBuilder
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = forward_static_call(
            [$resourceClass = $this->resourceClass, 'newModel']
        );

        /** @var QueryBuilder $query */
        $query = app()->make(QueryBuilder::class, [$resourceClass]);

        return $query->search($request, $model->newQuery(), null, [], [], TrashedStatus::fromBoolean($withTrashed))
                    ->tap(function (Builder $query) use ($request, $resourceClass, $model) {
                        $this->applyAssociatableCallbacks($query, $request, $resourceClass, $model);
                    });
    }

    /**
     * Format the given associatable resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model  $resource
     */
    public function formatAssociatableResource(NovaRequest $request, $resource): array
    {
        if (! $resource instanceof Resource) {
            $resource = Nova::newResourceFromModel($resource);
        }

        return array_filter([
            'avatar' => $resource->resolveAvatarUrl($request),
            'display' => $this->formatDisplayValue($resource),
            'subtitle' => $resource->subtitle(),
            'value' => Util::safeInt($resource->getKey()),
        ]);
    }

    /**
     * Specify if the related resource can be viewed.
     *
     * @return $this
     */
    public function viewable(bool $value = true)
    {
        $this->viewable = $value;

        return $this;
    }

    /**
     * Specify a callback that should be run when the field is filled.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):void)|null  $callback
     * @return $this
     */
    public function filled(?callable $callback)
    {
        $this->filledCallback = $callback;

        return $this;
    }

    /**
     * Set the value for the field.
     */
    public function setValue(mixed $value): void
    {
        $this->belongsToId = Util::safeInt($value);
        $this->value = $value;
    }

    /**
     * Set the attribute name of the inverse of the relationship.
     *
     * @return $this
     */
    public function inverse(string $inverse)
    {
        $this->inverse = $inverse;

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
     * Return the sortable uri key for the field.
     */
    #[\Override]
    public function sortableUriKey(): string
    {
        $request = app(NovaRequest::class);

        return $this->getRelationForeignKeyName($request->newResource()->resource->{$this->attribute}());
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter|null
     */
    protected function makeFilter(NovaRequest $request)
    {
        if (
            is_null($request->resource) || (
                $request->viaRelationship()
                && ($request->relationshipType ?? null) === 'hasMany'
                && $this->resourceClass::uriKey() === $request->viaResource
            )
        ) {
            return null;
        }

        return new BelongsToFilter($this, $request->resource);
    }

    /**
     * Define filterable attribute.
     */
    protected function filterableAttribute(NovaRequest $request): string
    {
        return $this->getRelationForeignKeyName($request->newResource()->resource->{$this->attribute}());
    }

    /**
     * Define the default filterable callback.
     *
     * @return callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder, mixed, string):void
     */
    protected function defaultFilterableCallback()
    {
        return function (NovaRequest $request, $query, $value, $attribute) {
            $query->where($attribute, '=', $value);
        };
    }

    /**
     * Prepare the field for JSON serialization.
     */
    #[\Override]
    public function serializeForFilter(): array
    {
        return transform($this->jsonSerialize(), function ($field) {
            return [
                'attribute' => $field['attribute'],
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
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return with(app(NovaRequest::class), function ($request) {
            $viewable = ! is_null($this->viewable) ? $this->viewable : $this->resourceClass::authorizedToViewAny($request);

            return array_merge([
                'belongsToId' => $this->belongsToId,
                'relationshipType' => $this->relationshipType(),
                'belongsToRelationship' => $this->belongsToRelationship,
                'debounce' => $this->debounce,
                'displaysWithTrashed' => $this->displaysWithTrashed,
                'label' => $this->resourceClass::label(),
                'peekable' => $this->isPeekable($request),
                'hasFieldsToPeekAt' => $this->hasFieldsToPeekAt($request),
                'resourceName' => $this->resourceName,
                'reverse' => $this->isReverseRelation($request),
                'searchable' => $this->isSearchable($request),
                'withSubtitles' => $this->withSubtitles,
                'showCreateRelationButton' => $this->createRelationShouldBeShown($request),
                'singularLabel' => $this->singularLabel,
                'viewable' => $viewable,
            ], parent::jsonSerialize());
        });
    }
}
