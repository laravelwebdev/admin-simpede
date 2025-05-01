<?php

namespace Laravel\Nova\Fields;

use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;

/**
 * @phpstan-import-type TFields from \Laravel\Nova\Resource
 *
 * @phpstan-type TPanelFields iterable<int, TFields>
 */
abstract class FieldMergeValue extends MergeValue
{
    use ConditionallyLoadsAttributes;

    /**
     * Prepare the given fields.
     *
     * @param  (callable():(iterable))|iterable  $fields
     * @return array<int, TFields>
     *
     * @phpstan-param (callable():(TPanelFields))|TPanelFields $fields
     *
     * @phpstan-return TPanelFields
     */
    protected function prepareFields(callable|iterable $fields): iterable
    {
        $fields = \is_callable($fields) ? \call_user_func($fields) : $fields;

        return collect($this->filter($fields instanceof Collection ? $fields->all() : $fields))
            ->reject(static fn ($field) => $field instanceof MissingValue)
            ->values()
            ->all();
    }

    /**
     * Transform each field in the panel using a callback.
     *
     * @param  callable(\Laravel\Nova\Fields\Field, int):mixed  $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        $this->data = Collection::make($this->data)
            ->transform(static function ($field, $key) use ($callback) {
                /**
                 * @var \Laravel\Nova\Fields\Field $field
                 * @var int $key
                 */
                \call_user_func($callback, $field, $key);

                return $field;
            })->all();

        return $this;
    }
}
