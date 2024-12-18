<?php

namespace Laravel\Nova\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified as Middleware;
use Illuminate\Support\Facades\Route;

class EnsureEmailIsVerified extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $redirectToRoute
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null
     */
    #[\Override]
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (is_null($redirectToRoute) && Route::has('nova.pages.verification.notice')) {
            $redirectToRoute = 'nova.pages.verification.notice';
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
