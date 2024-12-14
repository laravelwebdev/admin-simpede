<?php

namespace Laravel\Nova\Http\Controllers\Pages;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class UserSecurityController extends Controller
{
    public function show(NovaRequest $request): Response
    {
        abort_unless(Features::hasSecurityFeatures(), 404);

        return Inertia::render('Nova.UserSecurity', array_filter([
            'options' => config('fortify-options', []),
            'user' => transform(Nova::user($request), fn ($user) => [
                'two_factor_enabled' => ! is_null($user->two_factor_secret), // @phpstan-ignore property.notFound
            ]),
        ]));
    }
}
