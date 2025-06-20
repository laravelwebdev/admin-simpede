<?php

namespace Laravel\Nova\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Nova;
use Laravel\Nova\Util;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request):mixed  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (! \is_null($guard)) {
            trigger_deprecation('laravel/nova', '5.6.1', 'Guard parameter no longer supported via [%s] middleware', __CLASS__);
        }

        if (Auth::guard(Util::userGuard())->check()) {
            return redirect(Nova::path());
        }

        return $next($request);
    }
}
