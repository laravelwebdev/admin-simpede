<?php

namespace Laravel\Nova;

use ArrayAccess;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Http\Resources\DelegatesToResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonSerializable;
use Laravel\Nova\Fields\HasAttachments;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Scout\Searchable;

use function Orchestra\Sidekick\Eloquent\model_exists;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @phpstan-type TFields \Illuminate\Http\Resources\MissingValue|\Laravel\Nova\Fields\Field|\Laravel\Nova\Panel|\Laravel\Nova\ResourceToolElement
 *
 * @mixin TModel
 *
 * @method static static make(\Illuminate\Database\Eloquent\Model|null $resource)
 * @method mixed getKey()
 */
abstract class Resource implements ArrayAccess, JsonSerializable, UrlRoutable
{
    use Authorizable;
    use ConditionallyLoadsAttributes;
    use DelegatesToResource;
    use FillsFields;
    use HasLifecycleMethods;
    use Makeable;
    use PerformsQueries;
    use PerformsValidation;
    use ResolvesActions;
    use ResolvesCards;
    use ResolvesFields;
    use ResolvesFilters;
    use ResolvesLenses;
    use SupportsPolling;

    /**
     * The default displayable pivot class name.
     *
     * @var string
     */
    public const DEFAULT_PIVOT_NAME = 'Pivot';

    /**
     * The visual style used for the table. Available options are 'tight' and 'default'.
     *
     * @var string
     */
    public static $tableStyle = 'default';

    /**
     * Whether to show borders for each column on the X-axis.
     *
     * @var bool
     */
    public static $showColumnBorders = false;

    /**
     * The underlying model resource instance.
     *
     * @var TModel|null
     */
    public $resource;

    /**
     * The logical group associated with the resource.
     *
     * @var \Stringable|string
     */
    public static $group = 'Other';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The relationships that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $with = [];

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [];

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = true;

    /**
     * Indicates if the resource should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = true;

    /**
     * The number of results to display in the global search.
     *
     * @var int
     */
    public static $globalSearchResults = 5;

    /**
     * The number of results to display when searching relatable resource without Scout.
     *
     * @var int|null
     */
    public static $relatableSearchResults = null;

    /**
     * The number of results to display when searching the resource using Scout.
     *
     * @var int
     */
    public static $scoutSearchResults = 200;

    /**
     * Where should the global search link to?
     *
     * @var string
     */
    public static $globalSearchLink = 'detail';

    /**
     * Indicates if the resource should be searchable on the index view.
     *
     * @var bool
     */
    public static $searchable = true;

    /**
     * The pagination per-page options used the resource index.
     *
     * @var int|array<int, int>|null
     */
    public static $perPageOptions = [25, 50, 100];

    /**
     * The pagination per-page options used the resource via relationship.
     *
     * @var int
     *
     * @deprecated use `$perPageViaRelationshipOptions` instead.
     */
    public static $perPageViaRelationship = 5;

    /**
     * The number of resources to show per page via relationships.
     *
     * @var int|array<int, int>|null
     */
    public static $perPageViaRelationshipOptions = null;

    /**
     * The cached soft deleting statuses for various resources.
     *
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, bool>
     */
    public static $softDeletes = [];

    /**
     * Indicates whether Nova should check for modifications between viewing and updating a resource.
     *
     * @var bool
     */
    public static $trafficCop = true;

    /**
     * The maximum value of the resource's primary key column.
     *
     * @var int
     */
    public static $maxPrimaryKeySize = PHP_INT_MAX;

    /**
     * The debounce amount to use when searching this resource.
     *
     * @var float
     */
    public static $debounce = 0.5;

    /**
     * The click action to use when clicking on the resource in the table.
     * Can be one of: 'detail' (default), 'edit', 'select', 'preview', or 'ignore'.
     *
     * @var string
     */
    public static $clickAction = 'detail';

    /**
     * The callbacks to be called after saving an individual resource.
     *
     * @var array
     */
    public static $afterCallbacks = [];

    /**
     * Create a new resource instance.
     *
     * @param  TModel|null  $resource
     */
    public function __construct($resource = null)
    {
        $this->resource = $resource;
    }

    /**
     * Return a fresh resource instance.
     *
     * @return static<TModel>
     */
    public static function newResource()
    {
        return new static(static::newModel());
    }

    /**
     * Get a fresh instance of the model represented by the resource.
     *
     * @return TModel
     */
    public static function newModel()
    {
        /** @var TModel $model */
        $model = static::$model;

        return new $model(static::defaultAttributes());
    }

    /**
     * Get the default attributes for the model represented by the resource.
     *
     * @return array<string, mixed>
     */
    public static function defaultAttributes()
    {
        return [];
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    abstract public function fields(NovaRequest $request);

    /**
     * Get the underlying model instance for the resource.
     *
     * @return TModel|null
     */
    public function model()
    {
        return $this->resource;
    }

    /**
     * Return a replicated resource.
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public function replicate()
    {
        $model = $this->model();

        if (! model_exists($model)) {
            throw new InvalidArgumentException('Unable to replicate from non-existing resource');
        }

        return new static($model->replicate(
            $this->deletableFields(resolve(NovaRequest::class))
                ->reject(static function ($field) {
                    $uses = trait_uses_recursive($field);

                    /** @phpstan-ignore property.notFound */
                    return isset($uses[HasAttachments::class]) && $field->withFiles === false;
                })
                ->pluck('attribute')
                ->all()
        ));
    }

    /**
     * Get the logical group associated with the resource.
     *
     * @return \Stringable|string
     */
    public static function group()
    {
        return static::$group;
    }

    /**
     * Determine if this resource is available for navigation.
     *
     * @return bool
     */
    public static function availableForNavigation(Request $request)
    {
        return static::$displayInNavigation;
    }

    /**
     * Determine if this resource uses soft deletes.
     *
     * @return bool
     */
    public static function softDeletes()
    {
        if (isset(static::$softDeletes[static::$model])) {
            return static::$softDeletes[static::$model];
        }

        return static::$softDeletes[static::$model] = \in_array(
            SoftDeletes::class,
            class_uses_recursive(static::newModel())
        );
    }

    /**
     * Determine if this resource is searchable.
     *
     * @return bool
     */
    public static function searchable()
    {
        return (static::$searchable && ! empty(static::searchableColumns())) || (static::$searchable && static::usesScout());
    }

    /**
     * Determine whether the global search links will take the user to the detail page.
     *
     * @return \Stringable|string
     */
    public function globalSearchLink(NovaRequest $request)
    {
        return static::$globalSearchLink;
    }

    /**
     * Determine if this resource uses Laravel Scout.
     *
     * @return bool
     */
    public static function usesScout()
    {
        return \in_array(Searchable::class, class_uses_recursive(static::newModel()));
    }

    /**
     * Get the searchable columns for the resource.
     *
     * @return array
     */
    public static function searchableColumns()
    {
        return empty(static::$search)
                    ? [static::newModel()->getKeyName()]
                    : static::$search;
    }

    /**
     * Get the displayable label of the resource.
     *
     * @return \Stringable|string
     */
    public static function label()
    {
        return Str::plural(Str::title(Str::snake(class_basename(\get_called_class()), ' ')));
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return \Stringable|string
     */
    public static function singularLabel()
    {
        return Str::singular(static::label());
    }

    /**
     * Get the value that should be displayed to represent the resource.
     *
     * @return \Stringable|string
     */
    public function title()
    {
        return (string) data_get($this, static::$title);
    }

    /**
     * Get the search result subtitle for the resource.
     *
     * @return \Stringable|string|null
     */
    public function subtitle()
    {
        return null;
    }

    /**
     * Get the text for the create resource button.
     *
     * @return \Stringable|string|null
     */
    public static function createButtonLabel()
    {
        return Nova::__('Create :resource', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the text for the update resource button.
     *
     * @return \Stringable|string|null
     */
    public static function updateButtonLabel()
    {
        return Nova::__('Update :resource', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function uriKey()
    {
        return Str::plural(Str::kebab(class_basename(\get_called_class())));
    }

    /**
     * Get meta-information about this resource for client-side consumption.
     *
     * @return array<string, mixed>
     */
    public static function additionalInformation(Request $request)
    {
        return [];
    }

    /**
     * The pagination per-page options are configured for this resource.
     *
     * @return array<int, int>
     */
    public static function perPageOptions()
    {
        return transform(
            static::$perPageOptions,
            static fn ($perPageOptions) => Arr::wrap($perPageOptions),
            [static::newModel()->getPerPage()],
        );
    }

    /**
     * The pagination per-page options are configured for this resource via relationship.
     *
     * @return array<int, int>
     */
    public static function perPageViaRelationshipOptions()
    {
        if (\is_null(static::$perPageViaRelationshipOptions) && \is_int(static::$perPageViaRelationship)) {
            static::$perPageViaRelationshipOptions = [static::$perPageViaRelationship];
        }

        return transform(
            static::$perPageViaRelationshipOptions,
            static fn ($perPageOptions) => Arr::wrap($perPageOptions),
            [5],
        );
    }

    /**
     * Indicates whether Nova should check for modifications between viewing and updating a resource.
     *
     * @return bool
     */
    public static function trafficCop(Request $request)
    {
        return static::$trafficCop;
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @param  \Illuminate\Support\Collection<int, \Laravel\Nova\Fields\Field>  $fields
     * @return array<string, mixed>
     */
    public function serializeForIndex(NovaRequest $request, $fields = null)
    {
        return array_merge($this->serializeWithId($fields ?: $this->indexFields($request)), [
            'title' => $this->title(),
            'actions' => $this->availableActionsOnTableRow($request),
            'authorizedToView' => $this->authorizedToView($request),
            'authorizedToCreate' => $this->authorizedToCreate($request),
            'authorizedToReplicate' => $this->authorizedToReplicate($request),
            'authorizedToUpdate' => $this->authorizedToUpdateForSerialization($request),
            'authorizedToDelete' => $this->authorizedToDeleteForSerialization($request),
            'authorizedToRestore' => static::softDeletes() && $this->authorizedToRestore($request),
            'authorizedToForceDelete' => static::softDeletes() && $this->authorizedToForceDelete($request),
            'authorizedToImpersonate' => $this->authorizedToImpersonate($request),
            'previewHasFields' => $this->previewFieldsCount($request) > 0,
            'softDeletes' => static::softDeletes(),
            'softDeleted' => $this->isSoftDeleted(),
        ]);
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @return array<string, mixed>
     */
    public function serializeForDetail(NovaRequest $request, self $resource)
    {
        return array_merge($this->serializeWithId($this->detailFieldsWithinPanels($request, $resource)), [
            'title' => $this->title(),
            'authorizedToCreate' => $this->authorizedToCreate($request),
            'authorizedToReplicate' => $this->authorizedToReplicate($request),
            'authorizedToUpdate' => $this->authorizedToUpdate($request),
            'authorizedToDelete' => $this->authorizedToDelete($request),
            'authorizedToRestore' => static::softDeletes() && $this->authorizedToRestore($request),
            'authorizedToForceDelete' => static::softDeletes() && $this->authorizedToForceDelete($request),
            'authorizedToImpersonate' => $this->authorizedToImpersonate($request),
            'softDeletes' => static::softDeletes(),
            'softDeleted' => $this->isSoftDeleted(),
        ]);
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function serializeForPreview(NovaRequest $request)
    {
        /** @phpstan-ignore argument.type */
        return array_merge($this->serializeWithId($this->previewFields($request)), [
            'title' => $this->title(),
            'softDeleted' => $this->isSoftDeleted(),
        ]);
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function serializeForPeeking(NovaRequest $request)
    {
        return array_merge($this->serializeWithId($this->peekableFields($request)), [
            'title' => $this->title(),
            'softDeleted' => $this->isSoftDeleted(),
        ]);
    }

    /**
     * Determine if the resource may be updated, factoring in attachments.
     *
     * @return bool
     */
    protected function authorizedToUpdateForSerialization(NovaRequest $request)
    {
        if ($request->viaManyToMany()) {
            return $request->findParentResourceOrFail()->authorizedToAttach(
                $request,
                $this->model()
            );
        }

        return $this->authorizedToUpdate($request);
    }

    /**
     * Determine if the resource may be deleted, factoring in detachments.
     *
     * @return bool
     */
    protected function authorizedToDeleteForSerialization(NovaRequest $request)
    {
        if ($request->viaManyToMany()) {
            return $request->findParentResourceOrFail()->authorizedToDetach(
                $request,
                $this->model(),
                $request->viaRelationship
            );
        }

        return $this->authorizedToDelete($request);
    }

    /**
     * Determine if the resource is soft deleted.
     *
     * @return bool
     */
    public function isSoftDeleted()
    {
        return static::softDeletes() && $this->resource->trashed();
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->serializeWithId($this->resolveFields(
            resolve(NovaRequest::class)
        ));
    }

    /**
     * Prepare the resource for JSON serialization using the given fields.
     *
     * @param  \Illuminate\Support\Collection<int, \Laravel\Nova\Fields\Field>  $fields
     * @return array
     */
    protected function serializeWithId(Collection $fields)
    {
        return [
            'id' => $fields->whereInstanceOf(ID::class)->first() ?: ID::forModel($this->resource),
            'fields' => $fields->all(),
        ];
    }

    /**
     * Return the location to redirect the user after creation.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @return \Laravel\Nova\URL|string
     */
    public static function redirectAfterCreate(NovaRequest $request, Resource $resource)
    {
        return '/resources/'.static::uriKey().'/'.$resource->getKey();
    }

    /**
     * Return the location to redirect the user after an update.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @return \Laravel\Nova\URL|string
     */
    public static function redirectAfterUpdate(NovaRequest $request, Resource $resource)
    {
        return '/resources/'.static::uriKey().'/'.$resource->getKey();
    }

    /**
     * Return the location to redirect the user after deletion.
     *
     * @return \Laravel\Nova\URL|string|null
     */
    public static function redirectAfterDelete(NovaRequest $request)
    {
        return null;
    }

    /**
     * Return the maximum primary key size for the Resource.
     *
     * @return int
     */
    public static function maxPrimaryKeySize()
    {
        return static::$maxPrimaryKeySize;
    }

    /**
     * Determine whether to show borders for each column on the X-axis.
     *
     * @return bool
     */
    public static function showColumnBorders()
    {
        return static::$showColumnBorders;
    }

    /**
     * Get the visual style that should be used for the table.
     *
     * @return string
     */
    public static function tableStyle()
    {
        return static::$tableStyle;
    }

    /**
     * Get the click action to use when clicking on the resource in the table.
     *
     * Can be one of: 'detail' (default), 'edit', 'select', 'preview', or 'ignore'.
     *
     * @return string
     */
    public static function clickAction()
    {
        return static::$clickAction;
    }

    /**
     * Return the menu item that should represent the resource.
     *
     * @return \Laravel\Nova\Menu\MenuItem
     */
    public function menu(Request $request)
    {
        return MenuItem::resource(static::class);
    }
}
