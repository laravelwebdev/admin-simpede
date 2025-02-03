<?php

namespace Laravel\Nova\Auth\Actions;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LogoutResponse as Responsable;
use Laravel\Nova\Nova;

class LogoutResponse implements Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $redirect = redirect()->intended(route('welcome'));

        return $request->wantsJson()
            ? new JsonResponse([
                'redirect' => $redirect->getTargetUrl(),
            ], 200)
            : $redirect;
    }
}
