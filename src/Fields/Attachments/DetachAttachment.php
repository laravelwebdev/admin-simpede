<?php

namespace Laravel\Nova\Fields\Attachments;

use Laravel\Nova\Http\Requests\NovaRequest;

class DetachAttachment
{
    /**
     * The attachment model.
     *
     * @var class-string<\Laravel\Nova\Fields\Attachments\Attachment>
     */
    public static $model = Attachment::class;

    /**
     * Delete an attachment from the field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return void
     */
    public function __invoke(NovaRequest $request)
    {
        static::$model::where('url', $request->attachmentUrl)
                    ->get()
                    ->each
                    ->purge();
    }
}
