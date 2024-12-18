<?php

namespace Laravel\Nova\Auth\Actions;

use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as Action;
use Laravel\Fortify\Fortify;
use Laravel\Nova\Nova;

class RedirectIfTwoFactorAuthenticatable extends Action
{
    /**
     * Attempt to validate the incoming credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    #[\Override]
    protected function validateCredentials($request)
    {
        $authenticateUsingCallback = Fortify::$authenticateUsingCallback;

        if (! Nova::fortify()->usingIdenticalGuardOrModel()) {
            Fortify::$authenticateUsingCallback = null;
        }

        return tap(parent::validateCredentials($request), function () use ($authenticateUsingCallback) {
            Fortify::$authenticateUsingCallback = $authenticateUsingCallback;
        });
    }
}
