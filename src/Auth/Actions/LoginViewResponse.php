<?php

namespace Laravel\Nova\Auth\Actions;

use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginViewResponse as Responsable;
use Laravel\Nova\Nova;

class LoginViewResponse implements Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $initialYear = config('app.initialyear', date('Y'));
        $currentYear = date('Y');
        $years = $initialYear <= $currentYear ? range($initialYear, $currentYear) : [];
        return Inertia::render('Nova.Login', [
            'username' => Nova::fortify()->username,
            'email' => Nova::fortify()->email,
            'years' => $years,
        ])->toResponse($request);
    }
}
