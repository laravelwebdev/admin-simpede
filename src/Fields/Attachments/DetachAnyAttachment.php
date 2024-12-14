<?php

namespace Laravel\Nova\Fields\Attachments;

use Illuminate\Http\Request;

class DetachAnyAttachment
{
    /**
     * Delete any attachments from the field.
     */
    public function __invoke(Request $request): void
    {
        call_user_func(new DetachAttachment, $request);
        call_user_func(new DetachPendingAttachment, $request);
    }
}
