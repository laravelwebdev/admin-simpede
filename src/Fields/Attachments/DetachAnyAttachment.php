<?php

namespace Laravel\Nova\Fields\Attachments;

use Illuminate\Http\Request;

class DetachAnyAttachment
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __invoke(Request $request)
    {
        call_user_func(new DetachAttachment, $request);
        call_user_func(new DetachPendingAttachment, $request);
    }
}
