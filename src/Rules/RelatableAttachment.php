<?php

namespace Laravel\Nova\Rules;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Nova;

class RelatableAttachment extends Relatable
{
    /**
     * Authorize that the user is allowed to relate this resource.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     */
    #[\Override]
    protected function authorize(string $resourceClass, Model $model): bool
    {
        $parentResource = rescue(
            fn () => $this->request->findResourceOrFail(),
            Nova::newResourceFromModel($this->request->findModelOrFail()),
            false,
        );

        return $parentResource->authorizedToAttachAny(
            $this->request, $model
        ) || $parentResource->authorizedToAttach(
            $this->request, $model
        );
    }

    /**
     * Determine if the relationship is "full".
     */
    #[\Override]
    protected function relationshipIsFull(Model $model, string $attribute, mixed $value): bool
    {
        return false;
    }
}
