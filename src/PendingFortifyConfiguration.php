<?php

namespace Laravel\Nova;

use Closure;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as FortifyRedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\ConfirmPasswordViewResponse as ConfirmPasswordViewResponseContract;
use Laravel\Fortify\Contracts\FailedPasswordConfirmationResponse as FailedPasswordConfirmationResponseContract;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LoginViewResponse as LoginViewResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;
use Laravel\Fortify\Contracts\PasswordUpdateResponse as PasswordUpdateResponseContract;
use Laravel\Fortify\Contracts\RequestPasswordResetLinkViewResponse as RequestPasswordResetLinkViewResponseContract;
use Laravel\Fortify\Contracts\ResetPasswordViewResponse as ResetPasswordViewResponseContract;
use Laravel\Fortify\Contracts\ResetsUserPasswords as ResetsUserPasswordsContract;
use Laravel\Fortify\Contracts\TwoFactorChallengeViewResponse as TwoFactorChallengeViewResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Contracts\UpdatesUserPasswords as UpdatesUserPasswordsContract;
use Laravel\Fortify\Contracts\VerifyEmailViewResponse as VerifyEmailViewResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Responses\RedirectAsIntended;
use Laravel\Nova\Auth\Actions\ConfirmPasswordViewResponse;
use Laravel\Nova\Auth\Actions\FailedPasswordConfirmationResponse;
use Laravel\Nova\Auth\Actions\LoginResponse;
use Laravel\Nova\Auth\Actions\LoginViewResponse;
use Laravel\Nova\Auth\Actions\LogoutResponse;
use Laravel\Nova\Auth\Actions\PasswordConfirmedResponse;
use Laravel\Nova\Auth\Actions\PasswordUpdateResponse;
use Laravel\Nova\Auth\Actions\RedirectAsIntendedForNova;
use Laravel\Nova\Auth\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Nova\Auth\Actions\RequestPasswordResetLinkViewResponse;
use Laravel\Nova\Auth\Actions\ResetPasswordViewResponse;
use Laravel\Nova\Auth\Actions\ResetUserPassword;
use Laravel\Nova\Auth\Actions\TwoFactorChallengeViewResponse;
use Laravel\Nova\Auth\Actions\TwoFactorLoginResponse;
use Laravel\Nova\Auth\Actions\UpdateUserPassword;
use Laravel\Nova\Auth\Actions\VerifyEmailViewResponse;
use Laravel\Nova\Events\ServingNova;

class PendingFortifyConfiguration
{
    /**
     * List of Laravel Fortify features.
     *
     * @var array<int, string>|null
     */
    public ?array $features = null;

    /**
     * List of Laravel Fortify options.
     *
     * @var array<string, mixed>|null
     */
    public ?array $options = null;

    /**
     * The username used for authentication.
     */
    public string $username;

    /**
     * The name of the email address request variable / field.
     */
    public string $email;

    /**
     * List of Laravel Fortify features.
     */
    protected ?array $cachedConfig = null;

    /**
     * List of Laravel Fortify features.
     */
    protected ?array $cachedOptionsConfig = null;

    /**
     * The callback that is responsible for building the authentication pipeline array, if applicable.
     *
     * @var (callable(\Illuminate\Http\Request):(array<int, string|class-string>))|null
     */
    protected $authenticateThroughCallback = null;

    /**
     * The original callback that is responsible for building the authentication pipeline array, if applicable.
     *
     * @var (callable(\Illuminate\Http\Request):(array<int, string|class-string>))|null
     */
    protected $originalAuthenticateThroughCallback = null;

    /**
     * The callback that is responsible for validating authentication credentials, if applicable.
     *
     * @var (callable(\Illuminate\Http\Request):(mixed|null))|null
     */
    protected $authenticateUsingCallback = null;

    /**
     * The original callback that is responsible for validating authentication credentials, if applicable.
     *
     * @var (callable(\Illuminate\Http\Request):(mixed|null))|null
     */
    protected $originalAuthenticateUsingCallback = null;

    /**
     * The callback that is responsible for confirming user passwords.
     *
     * @var (callable(mixed, ?string):(bool))|null
     */
    protected $confirmPasswordsUsingCallback = null;

    /**
     * The original callback that is responsible for confirming user passwords.
     *
     * @var (callable(mixed, ?string):(bool))|null
     */
    protected $originalConfirmPasswordsUsingCallback = null;

    /**
     * Determined whether Fortify configurations and static props has been cached.
     */
    protected bool $cached = false;

    /**
     * Determine whether Foritfy is configured to load authentication routes for frontend.
     */
    protected ?bool $withFrontendRoutes = null;

    /**
     * Construct a new instance.
     */
    public function __construct()
    {
        $this->username = Fortify::username();
        $this->email = Fortify::email();
    }

    /**
     * Set Laravel Fortify enabled features.
     *
     * @param  (\Closure():(array<int, string>|null))|array<int, string>|null  $features
     * @return $this
     */
    public function features(Closure|array|null $features = null)
    {
        $this->cachedConfig = config('fortify', []);
        $this->cachedOptionsConfig = config('fortify-options', []);

        if (! is_null($features)) {
            $this->features = collect(Arr::wrap(value($features)))->merge(array_filter([
                Nova::routes()->withPasswordReset ? Features::resetPasswords() : null,
            ]))->unique()->all();

            $this->options = config('fortify-options');
        }

        return $this;
    }

    /**
     * Determine if the given feature is enabled.
     */
    public function enabled(string $feature): bool
    {
        return in_array($feature, $this->features ?? config('fortify.features', []));
    }

    /**
     * Determine if the application is using any security profile features.
     */
    public function hasSecurityFeatures(): bool
    {
        return $this->enabled(Features::updatePasswords()) ||
               $this->canManageTwoFactorAuthentication();
    }

    /**
     * Determine if the application can manage two factor authentication.
     */
    public function canManageTwoFactorAuthentication(): bool
    {
        return $this->enabled(Features::twoFactorAuthentication());
    }

    /**
     * Set the username used for authentication.
     *
     * @return $this
     */
    public function usernameUsing(string $attribute)
    {
        $this->username = $attribute;

        return $this;
    }

    /**
     * Set the email used for authentication.
     *
     * @return $this
     */
    public function emailUsing(string $attribute)
    {
        $this->email = $attribute;

        return $this;
    }

    /**
     * Register a callback that is responsible for building the authentication pipeline array.
     *
     * @param  callable(\Illuminate\Http\Request):(array<int, string|class-string>)  $callback
     * @return $this
     */
    public function authenticateThrough(callable $callback)
    {
        $this->authenticateThroughCallback = $callback;

        return $this;
    }

    /**
     * Register a callback that is responsible for validating incoming authentication credentials.
     *
     * @param  callable(\Illuminate\Http\Request):(mixed|null)  $callback
     * @return $this
     */
    public function authenticateUsing(callable $callback)
    {
        $this->authenticateUsingCallback = $callback;

        return $this;
    }

    /**
     * Register a callback responsible for confirming existing user passwords as valid.
     *
     * @param  callable(mixed, ?string):bool  $callback
     * @return $this
     */
    public function confirmPasswordsUsing(callable $callback)
    {
        $this->confirmPasswordsUsingCallback = $callback;

        return $this;
    }

    /**
     * Determine if Laravel Fortify uses the same auth guard as Nova.
     */
    public function usingIdenticalGuardOrModel(): bool
    {
        $fortifyGuard = config('fortify.guard') ?? config('auth.defaults.guard');
        $novaGuard = Util::userGuard();

        if ($fortifyGuard === $novaGuard) {
            return true;
        }

        return Util::userModelFromGuard($fortifyGuard) === Util::userModelFromGuard($novaGuard);
    }

    /**
     * Synchronize Laravel Forify's configurations.
     */
    public function sync(): void
    {
        if ($this->cached === true) {
            return;
        }

        if (Nova::routes()->defaultAuthentication()) {
            config([
                'fortify.features' => $this->features ?? [],
                'fortify-options' => $this->options ?? [],
                'fortify.username' => $this->username,
                'fortify.email' => $this->email,
            ]);
        } else {
            $this->authenticateThroughCallback ??= $this->originalAuthenticateThroughCallback = Fortify::$authenticateThroughCallback;
            $this->authenticateUsingCallback ??= $this->originalAuthenticateUsingCallback = Fortify::$authenticateUsingCallback;
            $this->confirmPasswordsUsingCallback ??= $this->originalConfirmPasswordsUsingCallback = Fortify::$confirmPasswordsUsingCallback;

            config([
                'fortify.features' => $this->features ?? Arr::get($this->cachedConfig, 'features', []),
                'fortify-options' => $this->options ?? $this->cachedOptionsConfig,
                'fortify.username' => $this->username,
                'fortify.email' => $this->email,
            ]);

            Fortify::$authenticateThroughCallback = $this->authenticateThroughCallback;
            Fortify::$authenticateUsingCallback = $this->authenticateUsingCallback;
            Fortify::$confirmPasswordsUsingCallback = $this->confirmPasswordsUsingCallback;
        }

        $this->cached = true;
    }

    /**
     * Flush Laravel Forify's configurations.
     */
    public function flush(): void
    {
        if (! Nova::routes()->defaultAuthentication()) {
            config([
                'fortify' => $this->cachedConfig,
                'fortify-options' => $this->cachedOptionsConfig,
            ]);

            Fortify::$authenticateThroughCallback = $this->originalAuthenticateThroughCallback;
            Fortify::$authenticateUsingCallback = $this->originalAuthenticateUsingCallback;
            Fortify::$confirmPasswordsUsingCallback = $this->originalConfirmPasswordsUsingCallback;
        }

        $this->cached = false;
    }

    /**
     * Register Fortify.
     */
    public function register(?bool $routes = null): void
    {
        if (is_null($routes)) {
            $routes = Util::isFortifyRoutesRegisteredForFrontend();
        }

        if ($routes === false) {
            Fortify::ignoreRoutes();
        }

        $this->withFrontendRoutes = $routes;
    }

    /**
     * Bootstrap the registered Nova routes.
     */
    public function bootstrap(): void
    {
        /** @var \Laravel\Nova\PendingRouteRegistration $routes */
        $routes = Nova::routes();

        if ($this->withFrontendRoutes === true && $routes->withAuthentication === false && $routes->withPasswordReset === false) {
            return;
        }

        Nova::serving(function (ServingNova $event) {
            $this->sync();

            /** @var \Illuminate\Contracts\Foundation\Application $app */
            $app = $event->app;

            $app->scoped(StatefulGuard::class, static fn () => Auth::guard(Util::userGuard()));
            $app->scoped(RedirectAsIntended::class, RedirectAsIntendedForNova::class);

            $app->scoped(LoginViewResponseContract::class, LoginViewResponse::class);
            $app->scoped(LoginResponseContract::class, LoginResponse::class);
            $app->scoped(LogoutResponseContract::class, LogoutResponse::class);

            $app->scoped(ResetPasswordViewResponseContract::class, ResetPasswordViewResponse::class);
            $app->scoped(RequestPasswordResetLinkViewResponseContract::class, RequestPasswordResetLinkViewResponse::class);
            $app->scoped(ResetsUserPasswordsContract::class, ResetUserPassword::class);

            $app->scoped(VerifyEmailViewResponseContract::class, VerifyEmailViewResponse::class);

            $app->scoped(PasswordUpdateResponseContract::class, PasswordUpdateResponse::class);
            $app->scoped(UpdatesUserPasswordsContract::class, UpdateUserPassword::class);

            $app->scoped(ConfirmPasswordViewResponseContract::class, ConfirmPasswordViewResponse::class);
            $app->scoped(PasswordConfirmedResponseContract::class, PasswordConfirmedResponse::class);
            $app->scoped(FailedPasswordConfirmationResponseContract::class, FailedPasswordConfirmationResponse::class);

            $app->scoped(TwoFactorChallengeViewResponseContract::class, TwoFactorChallengeViewResponse::class);
            $app->scoped(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);

            $app->scoped(FortifyRedirectIfTwoFactorAuthenticatable::class, RedirectIfTwoFactorAuthenticatable::class);

            ResetPassword::toMailUsing(static function ($notifiable, $token) {
                return (new MailMessage)
                    ->subject(Nova::__('Reset Password Notification'))
                    ->line(Nova::__('You are receiving this email because we received a password reset request for your account.'))
                    ->action(Nova::__('Reset Password'), route('nova.pages.password.reset', ['token' => $token]))
                    ->line(Nova::__('If you did not request a password reset, no further action is required.'));
            });
        });
    }
}
