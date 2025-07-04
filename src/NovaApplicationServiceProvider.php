<?php

namespace Laravel\Nova;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Exceptions\NovaExceptionHandler;

class NovaApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Nova::serving(function (ServingNova $event) {
            $this->authorization();
            $this->registerExceptionHandler();
            $this->resources();
            Nova::dashboards($this->dashboards());
            Nova::tools($this->tools());
        });
    }

    /**
     * Bootstrap authentication services.
     *
     * @return void
     */
    protected function bootAuthentication()
    {
        //
    }

    /**
     * Bootstrap route services.
     *
     * @return void
     */
    protected function bootRoutes()
    {
        $this->routes();

        if (! $this->app->routesAreCached()) {
            Nova::routes()->bootstrap($this->app);
        }
    }

    /**
     * Register the Fortify configurations.
     *
     * @return void
     */
    protected function fortify()
    {
        Nova::fortify()
            ->register();
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->withoutEmailVerificationRoutes()
            ->register();
    }

    /**
     * Configure the Nova authorization services.
     *
     * @return void
     */
    protected function authorization()
    {
        Nova::auth(static function ($request) {
            return app()->environment('local') ||
                   Gate::check('viewNova', [Nova::user($request)]);
        });
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', static fn ($user) => \in_array($user->email, [
            //
        ]));
    }

    /**
     * Get the cards that should be displayed on the Nova dashboard.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [];
    }

    /**
     * Register Nova's custom exception handler.
     *
     * @return void
     */
    protected function registerExceptionHandler()
    {
        app()->bind(ExceptionHandler::class, NovaExceptionHandler::class);
    }

    /**
     * Register the application's Nova resources.
     *
     * @return void
     */
    protected function resources()
    {
        Nova::resourcesIn(app_path('Nova'));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadJsonTranslationsFrom(lang_path('vendor/nova'));

        $this->fortify();

        $this->booted(function () {
            $this->gate();
            $this->bootAuthentication();
            $this->bootRoutes();
        });
    }
}
