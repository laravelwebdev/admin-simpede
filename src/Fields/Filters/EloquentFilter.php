<?php

namespace Laravel\Nova\Fields\Filters;

/**
 * @template TField of \Laravel\Nova\Contracts\FilterableField&\Laravel\Nova\Contracts\RelatableField&\Laravel\Nova\Fields\Field
 */
class EloquentFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'eloquent-field';

    /**
     * Get the key for the filter.
     *
     * @return string
     */
    public function key()
    {
        /** @phpstan-ignore property.notFound */
        return 'resource:'.$this->field->resourceClass::uriKey().':'.$this->field->attribute;
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function serializeField()
    {
        if (method_exists($this->field, 'serializeForFilter')) {
            return $this->field->serializeForFilter();
        }

        return $this->field->jsonSerialize();
    }
}
