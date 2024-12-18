<?php

namespace Laravel\Nova\Auth\Actions;

use App\Models\Pengelola;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as Responsable;
use Laravel\Nova\Nova;

class LoginResponse implements Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $roles = Pengelola::cache()->get('all')->where('user_id', Auth::user()->id)->whereNull('inactive')->pluck('role')->toArray();
        $redirect = redirect()->intended(Nova::initialPathUrl($request));
        session(['year' => $request->input('year')]);
        session(['role' => $roles]);
        return $request->wantsJson()
            ? new JsonResponse([
                'redirect' => $redirect->getTargetUrl(),
            ], 200)
            : $redirect;
    }
}
