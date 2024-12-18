<?php

namespace Laravel\Nova\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Laravel\Nova\Http\Requests\NovaRequest;

class Password extends Field
{
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'password-field';

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object|array  $resource
     */
    #[\Override]
    protected function resolveAttribute($resource, string $attribute): mixed
    {
        return '';
    }

    /**
     * Resolve the default value for an Action field.
     */
    #[\Override]
    public function resolveForAction(NovaRequest $request): void
    {
        $this->value = '';
    }

    /**
     * Resolve the field's value for display.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object|array  $resource
     */
    #[\Override]
    public function resolveForDisplay($resource, ?string $attribute = null): void
    {
        $this->value = '';
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    #[\Override]
    protected function fillAttributeFromRequest(NovaRequest $request, string $requestAttribute, object $model, string $attribute): void
    {
        if (! empty($request[$requestAttribute])) {
            $model->{$attribute} = Hash::make($request[$requestAttribute]);
        }
    }
}
