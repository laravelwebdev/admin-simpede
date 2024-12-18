<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Contracts\Previewable;
use Laravel\Nova\Http\Requests\NovaRequest;

class FieldPreviewController extends Controller
{
    /**
     * Delete the file at the given field.
     */
    public function __invoke(NovaRequest $request): JsonResponse
    {
        $request->validate(['value' => ['nullable', 'string']]);

        $resource = $request->newResource();

        /** @var \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\Previewable $field */
        $field = $resource->availableFields($request)
                    ->whereInstanceOf(Previewable::class)
                    ->findFieldByAttributeOrFail($request->field);

        return response()->json([
            'preview' => $field->previewFor($request->value),
        ]);
    }
}
