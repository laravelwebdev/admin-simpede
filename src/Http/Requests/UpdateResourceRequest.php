<?php

namespace Laravel\Nova\Http\Requests;

class UpdateResourceRequest extends NovaRequest
{
    /**
     * Determine if this request is an update or update-attached request.
     */
    #[\Override]
    public function isUpdateOrUpdateAttachedRequest(): bool
    {
        return true;
    }
}
