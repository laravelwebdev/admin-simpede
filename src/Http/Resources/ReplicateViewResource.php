<?php

namespace Laravel\Nova\Http\Resources;

use Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest;
use Laravel\Nova\Resource as NovaResource;

class ReplicateViewResource extends CreateViewResource
{
    /**
     * Construct a new Create View Resource.
     *
     * @return void
     */
    public function __construct(protected string|int|null $fromResourceId = null)
    {
        //
    }

    /**
     * Get current resource for the request.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function newResourceWith(ResourceCreateOrAttachRequest $request): NovaResource
    {
        $query = $request->findModelQuery($this->fromResourceId);

        $resource = $request->resource();
        $resource::replicateQuery($request, $query);

        $resource = $request->newResourceWith($query->firstOrFail());

        $resource->authorizeToReplicate($request);

        return $resource->replicate();
    }
}
