<?php

namespace Laravel\Nova\Auth\Actions;

use Laravel\Fortify\Http\Responses\RedirectAsIntended;
use Laravel\Nova\Nova;

class RedirectAsIntendedForNova extends RedirectAsIntended
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[\Override]
    public function toResponse($request)
    {
        return redirect()->intended(Nova::initialPathUrl($request));
    }
}
