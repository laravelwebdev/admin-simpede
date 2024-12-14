<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\ResourceSearchRequest;
use Laravel\Nova\Resource;
use Laravel\Nova\Util;

class ResourceSearchController extends Controller
{
    /**
     * List the resources for administration.
     */
    public function __invoke(ResourceSearchRequest $request): JsonResponse
    {
        $resource = $request->resource();

        $withTrashed = $this->shouldIncludeTrashed(
            $request, $resource
        );

        return response()->json([
            'resources' => $request->searchIndex()
                        ->mapInto($resource)
                        ->map(fn ($resource) => $this->transformResult($request, $resource))
                        ->values(),
            'softDeletes' => $resource::softDeletes(),
            'withTrashed' => $withTrashed,
        ]);
    }

    /**
     * Determine if the query should include trashed models.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     */
    protected function shouldIncludeTrashed(NovaRequest $request, string $resourceClass): bool
    {
        if ($request->withTrashed === 'true') {
            return true;
        }

        $model = $resourceClass::newModel();

        if ($request->current && empty($request->search) && $resourceClass::softDeletes()) {
            $model = $model->newQueryWithoutScopes()->find($request->current);

            /** @phpstan-ignore method.notFound */
            return $model ? $model->trashed() : false;
        }

        return false;
    }

    /**
     * Transform the result from resource.
     */
    protected function transformResult(NovaRequest $request, Resource $resource): array
    {
        return array_filter([
            'avatar' => $resource->resolveAvatarUrl($request),
            'display' => (string) $resource->title(),
            'subtitle' => $resource->subtitle(),
            'value' => Util::safeInt($resource->getKey()),
        ]);
    }
}
