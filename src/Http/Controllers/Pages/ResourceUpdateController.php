<?php

namespace Laravel\Nova\Http\Controllers\Pages;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Nova\Http\Requests\ResourceUpdateOrUpdateAttachedRequest;
use Laravel\Nova\Http\Resources\UpdateViewResource;
use Laravel\Nova\Menu\Breadcrumb;
use Laravel\Nova\Menu\Breadcrumbs;
use Laravel\Nova\Nova;

class ResourceUpdateController extends Controller
{
    /**
     * Show Resource Update page using Inertia.
     */
    public function __invoke(ResourceUpdateOrUpdateAttachedRequest $request): Response
    {
        abort_unless($request->findModelQuery()->exists(), 404);

        $resourceClass = $request->resource();

        return Inertia::render('Nova.Update', [
            'breadcrumbs' => $this->breadcrumbs($request),
            'resourceName' => $resourceClass::uriKey(),
            'resourceId' => $request->resourceId,
            'viaResource' => $request->query('viaResource') ?? '',
            'viaResourceId' => $request->query('viaResourceId') ?? '',
            'viaRelationship' => $request->query('viaRelationship') ?? '',
        ]);
    }

    /**
     * Get breadcrumb menu for the page.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function breadcrumbs(ResourceUpdateOrUpdateAttachedRequest $request): Breadcrumbs
    {
        $resourceClass = $request->resource();
        $resource = UpdateViewResource::make()->newResourceWith($request);

        return Breadcrumbs::make(
            collect([Breadcrumb::make(Nova::__('Resources'))])->when($request->viaRelationship(), static function ($breadcrumbs) use ($request) {
                return $breadcrumbs->push(
                    Breadcrumb::resource($request->viaResource()),
                    Breadcrumb::resource($request->findParentResource())
                );
            }, static function ($breadcrumbs) use ($resourceClass, $resource) {
                return $breadcrumbs->push(
                    Breadcrumb::resource($resourceClass),
                    Breadcrumb::resource($resource),
                );
            })->push(
                Breadcrumb::make(Nova::__('Update :resource', ['resource' => $resourceClass::singularLabel()]))
            )->all()
        );
    }
}
