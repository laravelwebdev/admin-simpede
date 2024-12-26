<?php

namespace Laravel\Nova\Http\Controllers;

use App\Models\Pengelola;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Nova\Nova;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;
    use ValidatesRequests;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('nova.guest:'.config('nova.guard'))->except('logout');
    }

    /**
     * Show the application's login form.
     *
     * @return \Inertia\Response|\Symfony\Component\HttpFoundation\Response
     */
    public function showLoginForm()
    {
        if ($loginPath = config('nova.routes.login', false)) {
            return Inertia::location($loginPath);
        }
        $years = range(config('nova.initialyear'), date('Y'));
        return Inertia::render('Nova.Login', ['years' => $years]);
    }

    /**
     * The user has been authenticated.
     *
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        $roles = Pengelola::cache()->get('all')->where('user_id', Auth::user()->id)->whereNull('inactive')->pluck('role')->toArray();
        $redirect = redirect()->intended($this->redirectPath($request));
        session(['year' => $request->input('year')]);
        session(['role' => $roles]);

        return $request->wantsJson()
            ? new JsonResponse([
                'redirect' => $redirect->getTargetUrl(),
            ], 200)
            : $redirect;
    }

    /**
     * Log the user out of the application.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return redirect()->intended($this->redirectPath($request));
    }

    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath(Request $request)
    {
        return Nova::url(Nova::resolveInitialPath($request));
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard(config('nova.guard'));
    }
}
