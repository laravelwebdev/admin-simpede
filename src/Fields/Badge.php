<?php

namespace Laravel\Nova\Fields;

use Exception;
use Illuminate\Support\Arr;
use Laravel\Nova\Badge as BadgeComponent;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Fields\Filters\SelectFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Stringable;

class Badge extends Field implements FilterableField, Unfillable
{
    use FieldFilterable;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'badge-field';

    /**
     * The text alignment for the field's text in tables.
     *
     * @var string
     */
    public $textAlign = 'center';

    /**
     * The labels that should be applied to the field's possible values.
     *
     * @var array<array-key, \Stringable|string>
     */
    public $labels = [];

    /**
     * The callback used to determine the field's label.
     *
     * @var (callable(mixed):(string))|null
     */
    public $labelCallback = null;

    /**
     * The mapping used for matching custom values to in-built badge types.
     *
     * @var array<array-key, string>
     */
    public $map = [];

    /**
     * Indicates if the field should show icons.
     *
     * @var bool
     */
    public $withIcons = false;

    /**
     * The built-in badge types and their corresponding CSS classes.
     *
     * @var array<array-key, string|array<int, string>>
     */
    public $types = [];

    /**
     * The icons that should be applied to the field's possible values.
     *
     * @var array<array-key, string>
     */
    public $icons = [
        'success' => 'check-circle',
        'info' => 'information-circle',
        'danger' => 'exclamation-circle',
        'warning' => 'exclamation-circle',
    ];

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|object|null  $attribute
     * @param  (callable(mixed, mixed, ?string):(mixed))|null  $resolveCallback
     */
    public function __construct($name, mixed $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->addTypes(BadgeComponent::$types)
            ->exceptOnForms();
    }

    /**
     * Add badge types and their corresponding CSS classes to the built-in ones.
     *
     * @param  array<array-key, string>  $types
     * @return $this
     */
    public function addTypes(array $types)
    {
        $this->types = array_merge($this->types, $types);

        return $this;
    }

    /**
     * Set the badge types and their corresponding CSS classes.
     *
     * @param  array<array-key, string>  $types
     * @return $this
     */
    public function types(array $types)
    {
        $this->types = $types;

        return $this;
    }

    /**
     * Set the labels for each possible field value.
     *
     * @param  array<array-key, string>  $labels
     * @return $this
     */
    public function labels(array $labels)
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * Set the callback to be used to determine the field's displayable label.
     *
     * @param  callable(mixed):string  $labelCallback
     * @return $this
     */
    public function label(callable $labelCallback)
    {
        $this->labelCallback = $labelCallback;

        return $this;
    }

    /**
     * Map the possible field values to the built-in badge types.
     *
     * @param  array<array-key, string>  $map
     * @return $this
     */
    public function map(array $map)
    {
        $this->map = $map;

        return $this;
    }

    /**
     * Set the field to display icons, optionally passing an icon mapping.
     *
     * @return $this
     */
    public function withIcons()
    {
        $this->withIcons = true;

        return $this;
    }

    /**
     * Set the icons for each possible field value.
     *
     * @param  array<array-key, string>  $icons
     * @return $this
     */
    public function icons(array $icons)
    {
        $this->withIcons = true;
        $this->icons = $icons;

        return $this;
    }

    /**
     * Resolve the Badge's CSS classes based on the field's value.
     *
     * @throws \Exception
     */
    public function resolveBadgeClasses(): array|string
    {
        $mappedValue = $this->map[$this->value] ?? $this->value;

        if (! isset($this->types[$mappedValue])) {
            throw new Exception("Error trying to find type [{$mappedValue}] inside of the field's type mapping.");
        }

        return $this->types[$mappedValue];
    }

    /**
     * Resolve the display label for the Badge.
     */
    public function resolveLabel(): Stringable|string
    {
        return $this->resolveLabelFor($this->value);
    }

    /**
     * Resolve the display label for the Badge.
     */
    protected function resolveLabelFor(string|int|null $value): Stringable|string
    {
        if (isset($this->labelCallback)) {
            return \call_user_func($this->labelCallback, $value);
        }

        return $this->labels[$value] ?? $value ?? '';
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter
     */
    protected function makeFilter(NovaRequest $request)
    {
        return new SelectFilter($this);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function serializeForFilter(): array
    {
        return transform(parent::jsonSerialize(), function ($field) {
            /** @phpstan-ignore argument.type */
            $options = collect($this->map)->keys()->transform(function ($value) {
                return ['value' => $value, 'label' => $this->resolveLabelFor($value)];
            })->all();

            return array_merge(
                Arr::only($field, [
                    'uniqueKey',
                    'name',
                    'attribute',
                ]),
                ['options' => $options]
            );
        });
    }

    /**
     * Resolve the display icon for the Badge.
     */
    public function resolveIcon(): string
    {
        $mappedValue = $this->map[$this->value] ?? $this->value;

        if (! isset($this->icons[$mappedValue])) {
            throw new Exception("Error trying to find icon [{$mappedValue}] inside of the field's icon mapping.");
        }

        return $this->icons[$mappedValue];
    }

    /**
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'label' => $this->resolveLabel(),
            'typeClass' => $this->resolveBadgeClasses(),
            'icon' => $this->withIcons ? $this->resolveIcon() : null,
        ]);
    }
}
