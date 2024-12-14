<?php

namespace Laravel\Nova\Fields;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Contracts\ListableField;
use Laravel\Nova\Contracts\PivotableField;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Contracts\Resolvable;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Laravel\Nova\ResourceTool;
use Laravel\Nova\ResourceToolElement;
use Laravel\Nova\Util;
use Stringable;

/**
 * @template TKey of int
 * @template TValue of \Laravel\Nova\Panel|\Laravel\Nova\ResourceToolElement|\Laravel\Nova\Fields\Field|\Illuminate\Http\Resources\MissingValue
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class FieldCollection extends Collection
{
    /**
     * Assign the fields with the given panels to their parent panel.
     *
     * @return static<TKey, TValue>
     */
    public function assignDefaultPanel(Stringable|string $label)
    {
        new Panel($label, $this->reject(fn ($field) => isset($field->panel)));

        return $this;
    }

    /**
     * Flatten stacked fields.
     *
     * @return static<int, TValue>
     */
    public function flattenStackedFields()
    {
        return $this->map(function ($field) {
            if ($field instanceof Stack) {
                return $field->fields()->all();
            }

            return $field;
        })->flatten();
    }

    /**
     * Find a given field by its attribute.
     *
     * @template TGetDefault
     *
     * @param  TGetDefault|(\Closure():(TGetDefault))  $default
     * @return TValue|TGetDefault
     */
    public function findFieldByAttribute(string $attribute, mixed $default = null)
    {
        return $this->first(function ($field) use ($attribute) {
            return isset($field->attribute) &&
                $field->attribute == $attribute;
        }, $default);
    }

    /**
     * Find a given field by its attribute.
     *
     * @return TValue
     */
    public function findFieldByAttributeOrFail(string $attribute)
    {
        return $this->first(function ($field) use ($attribute) {
            return isset($field->attribute) &&
                $field->attribute == $attribute;
        }, fn () => abort(404));
    }

    /**
     * Filter elements should be displayed for the given request.
     *
     * @return static<int, TValue>
     */
    public function authorized(Request $request)
    {
        return $this->filter->authorize($request)->values();
    }

    /**
     * Filter elements should be displayed for the given request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array  $resource
     * @return static<int, TValue>
     */
    public function resolve($resource)
    {
        return $this->each(function ($field) use ($resource) {
            if ($field instanceof Resolvable) {
                $field->resolve($resource);
            }
        });
    }

    /**
     * Resolve value of fields for display.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array  $resource
     * @return static<int, TValue>
     */
    public function resolveForDisplay($resource)
    {
        return $this->each(function ($field) use ($resource) {
            if ($field instanceof ListableField || ! $field instanceof Resolvable) {
                return;
            }

            if ($field->pivot) {
                $field->resolveForDisplay($resource->{$field->pivotAccessor} ?? new Pivot);
            } else {
                $field->resolveForDisplay($resource);
            }
        });
    }

    /**
     * Remove non-creation fields from the collection.
     *
     * @param  \Illuminate\Database\Eloquent\Model|object  $resource
     * @return static<int, \Laravel\Nova\Fields\Field>
     */
    public function onlyCreateFields(NovaRequest $request, $resource)
    {
        /** @phpstan-ignore return.type */
        return $this->reject(function ($field) use ($resource, $request) {
            return $field instanceof ListableField ||
                ($field instanceof ResourceTool || $field instanceof ResourceToolElement) ||
                $field->attribute === 'ComputedField' ||
                ($field instanceof ID && $field->attribute === $resource->getKeyName()) ||
                ! $field->isShownOnCreation($request);
        });
    }

    /**
     * Remove non-update fields from the collection.
     *
     * @param  \Illuminate\Database\Eloquent\Model|object  $resource
     * @return static<int, \Laravel\Nova\Fields\Field>
     */
    public function onlyUpdateFields(NovaRequest $request, $resource)
    {
        /** @phpstan-ignore return.type */
        return $this->reject(function ($field) use ($resource, $request) {
            return $field instanceof ListableField ||
                ($field instanceof ResourceTool || $field instanceof ResourceToolElement) ||
                $field->attribute === 'ComputedField' ||
                ($field instanceof ID && $field->attribute === $resource->getKeyName()) ||
                ! $field->isShownOnUpdate($request, $resource);
        });
    }

    /**
     * Filter fields for showing on detail.
     *
     * @param  \Illuminate\Database\Eloquent\Model|object  $resource
     * @return static<int, \Laravel\Nova\Fields\Field>
     */
    public function filterForDetail(NovaRequest $request, $resource)
    {
        /** @phpstan-ignore return.type */
        return $this->filter->isShownOnDetail($request, $resource)->values();
    }

    /**
     * Filter fields for showing on preview.
     *
     * @param  \Illuminate\Database\Eloquent\Model|object  $resource
     * @return static<int, \Laravel\Nova\Fields\Field>
     */
    public function filterForPreview(NovaRequest $request, $resource)
    {
        /** @phpstan-ignore return.type */
        return $this->filter->isShownOnPreview($request, $resource)->values();
    }

    /**
     * Filter fields for showing when peeking.
     *
     * @return static<int, \Laravel\Nova\Fields\Field>
     */
    public function filterForPeeking(NovaRequest $request)
    {
        /** @phpstan-ignore return.type */
        return $this->filter->isShownWhenPeeking($request)->values();
    }

    /**
     * Filter fields for showing on index.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array  $resource
     * @return static<int, \Laravel\Nova\Fields\Field>
     */
    public function filterForIndex(NovaRequest $request, $resource)
    {
        /** @phpstan-ignore return.type */
        return $this->filter->isShownOnIndex($request, $resource)->values();
    }

    /**
     * Reject if the field is readonly.
     *
     * @return static<int, TValue>
     */
    public function withoutReadonly(NovaRequest $request)
    {
        return $this->reject->isReadonly($request);
    }

    /**
     * Reject if the field is a missing value.
     *
     * @return static<int, \Laravel\Nova\Panel|\Laravel\Nova\ResourceToolElement|\Laravel\Nova\Fields\Field>
     */
    public function withoutMissingValues()
    {
        /** @phpstan-ignore return.type */
        return $this->reject(fn ($field) => $field instanceof MissingValue);
    }

    /**
     * Reject fields which use their own index listings.
     *
     * @return static<int, TValue>
     */
    public function withoutListableFields()
    {
        /** @phpstan-ignore return.type */
        return $this->reject(fn ($field) => $field instanceof ListableField);
    }

    /**
     * Reject if the field is unfillable.
     *
     * @return static<int, TValue>
     */
    public function withoutUnfillable()
    {
        /** @phpstan-ignore return.type */
        return $this->reject(fn ($field) => $field instanceof Unfillable);
    }

    /**
     * Reject fields which are actually ResourceTools.
     *
     * @return static<int, TValue>
     */
    public function withoutResourceTools()
    {
        /** @phpstan-ignore return.type */
        return $this->reject(fn ($field) => $field instanceof ResourceToolElement);
    }

    /**
     * Filter the fields to only many-to-many relationships.
     *
     * @return static<TKey, \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\PivotableField>
     */
    public function filterForManyToManyRelations()
    {
        /** @phpstan-ignore return.type */
        return $this->filter(fn ($field) => $field instanceof PivotableField);
    }

    /**
     * Reject if the field supports Filterable Field.
     *
     * @return static<TKey, \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\FilterableField>
     */
    public function withOnlyFilterableFields()
    {
        return $this->whereInstanceOf(Field::class)
            ->whereInstanceOf(FilterableField::class)
            ->reject(function ($field) {
                /** @var \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\FilterableField $field */
                return $field->isComputed() || is_null($field->filterableCallback);
            });
    }

    /**
     * Apply depends on for the request.
     *
     * @return $this
     */
    public function applyDependsOn(NovaRequest $request)
    {
        $this->each->applyDependsOn($request);

        return $this;
    }

    /**
     * Apply depends on for the request with default values.
     *
     * @return $this
     */
    public function applyDependsOnWithDefaultValues(NovaRequest $request)
    {
        $payloads = new LazyCollection(function () use ($request) {
            foreach ($this->items as $field) {
                $key = $field instanceof RelatableField ? $field->relationshipName() : $field->attribute;

                if ($field instanceof MorphTo) {
                    yield "{$key}_type" => $field->morphToType;
                }

                yield $key => Util::hydrate($field->resolveDependentValue($request));
            }
        });

        $this->each->applyDependsOn(
            NovaRequest::createFrom($request)->mergeIfMissing($payloads->all())
        );

        return $this;
    }
}
