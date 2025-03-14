<?php

namespace Laravel\Nova\Http\Controllers\Pages;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Nova\Http\Requests\ResourceUpdateOrUpdateAttachedRequest;
use Laravel\Nova\Menu\Breadcrumb;
use Laravel\Nova\Menu\Breadcrumbs;
use Laravel\Nova\Nova;

class AttachedResourceUpdateController extends Controller
{
    /**
     * Show Resource Update Attached page using Inertia.
     */
    public function __invoke(ResourceUpdateOrUpdateAttachedRequest $request): Response
    {
        $resourceClass = $request->resource();

        $isPolymorphic = static function ($query) {
            return \is_null($query) || \in_array($query, [true, 1, '1']);
        };

        $parentResource = $request->findResourceOrFail();

        return Inertia::render('Nova.UpdateAttached', [
            'breadcrumbs' => $this->breadcrumbs($request),
            'resourceName' => $resourceClass::uriKey(),
            'resourceId' => $request->resourceId,
            'relatedResourceName' => $request->relatedResource,
            'relatedResourceId' => $request->relatedResourceId,
            'viaRelationship' => $request->query('viaRelationship'),
            'viaPivotId' => $request->query('viaPivotId'),
            'polymorphic' => $isPolymorphic($request->query('polymorphic')),
            'viaResource' => $parentResource::uriKey(),
            'viaResourceId' => $parentResource->resource->getKey(),
            'parentResource' => [
                'name' => $parentResource->singularLabel(),
                'display' => $parentResource->title(),
            ],
        ]);
    }

    /**
     * Get breadcrumb menu for the page.
     */
    protected function breadcrumbs(ResourceUpdateOrUpdateAttachedRequest $request): Breadcrumbs
    {
        $resourceClass = $request->resource();
        $resource = $request->findResourceOrFail();
        $relatedResource = $request->findRelatedResourceOrFail($request->relatedResourceId);

        return Breadcrumbs::make([
            Breadcrumb::make(Nova::__('Resources')),
            Breadcrumb::resource($resourceClass),
            Breadcrumb::resource($resource),
            Breadcrumb::make(Nova::__('Update attached :resource: :title', [
                'resource' => $relatedResource::singularLabel(),
                'title' => $relatedResource->title(),
            ])),
        ]);
    }
}
