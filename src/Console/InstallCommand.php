<?php

namespace Laravel\Nova\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Configuration\ApplicationBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Nova\Util;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'nova:install')]
class InstallCommand extends Command
{
    use ResolvesStubPath;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nova:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Nova resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(Filesystem $files)
    {
        $this->components->task('Publishing Nova Assets / Resources...');
        $this->callSilent('nova:publish', ['--fortify' => true]);

        $this->components->task('Publishing Nova Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'nova-provider']);

        $this->components->task('Generating Main Dashboard...');
        $this->callSilent('nova:dashboard', ['name' => 'Main']);
        copy($this->resolveStubPath('/stubs/nova/main-dashboard.stub'), app_path('Nova/Dashboards/Main.php'));

        $this->installNovaServiceProvider($files);

        $this->components->task('Generating User Resource...');
        $this->callSilent('nova:resource', ['name' => 'User']);
        copy($this->resolveStubPath('/stubs/nova/user-resource.stub'), app_path('Nova/User.php'));

        $namespacedUserModel = Util::userModel();

        if (is_null($namespacedUserModel) && ! file_exists(app_path('Models/User.php'))) {
            $namespacedUserModel = 'App\User';
        }

        if (! is_null($namespacedUserModel) && $namespacedUserModel !== 'App\Models\User') {
            $baseUserModel = class_basename($namespacedUserModel);

            $searches = ['$model = \App\Models\User::class', 'class-string<\App\Models\User>'];
            $replacements = ['$model = \\'.$namespacedUserModel.'::class', 'class-string<\\'.$namespacedUserModel.'>'];

            if ($baseUserModel !== 'User') {
                $searches[] = 'use App\Models\User;';
                $replacements[] = 'use '.$namespacedUserModel.' as UserModel;';
                $searches[] = 'function (User $user)';
                $replacements[] = 'function (UserModel $user)';
            } else {
                $searches[] = 'use App\Models\User;';
                $replacements[] = 'use '.$namespacedUserModel.';';
            }

            file_put_contents(
                app_path('Nova/User.php'),
                str_replace(
                    $searches,
                    $replacements,
                    file_get_contents(app_path('Nova/User.php'))
                )
            );

            file_put_contents(
                app_path('Providers/NovaServiceProvider.php'),
                str_replace(
                    $searches,
                    $replacements,
                    file_get_contents(app_path('Providers/NovaServiceProvider.php'))
                )
            );
        }

        $this->setAppNamespace($files);

        $this->components->info('Nova scaffolding installed successfully.');
    }

    /**
     * Install the Nova service providers in the application configuration file.
     *
     * @return void
     */
    protected function installNovaServiceProvider(Filesystem $files)
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        $eol = Util::eol($appConfig);

        if (class_exists(ApplicationBuilder::class) && $files->exists(base_path('bootstrap/providers.php'))) {
            /** @phpstan-ignore staticMethod.notFound */
            ServiceProvider::addProviderToBootstrapFile("{$namespace}\\Providers\\NovaServiceProvider");

            $shouldEnablesFortifyRoutes = Collection::make([
                'App\Providers\JetstreamServiceProvider',
                'App\Providers\FortifyServiceProvider',
            ])->reject(fn ($provider) => in_array($provider, $this->laravel->getLoadedProviders()))
            ->isNotEmpty();

            if (
                (! $this->laravel['router']->has('login') || ! $shouldEnablesFortifyRoutes)
                && confirm('Would you like to use Nova as the default login?', true)
            ) {
                $files->put(
                    app_path('Providers/NovaServiceProvider.php'),
                    str_replace(
                        [$eol.'            ->withAuthenticationRoutes()'.$eol],
                        [$eol.'            ->withAuthenticationRoutes(default: true)'.$eol],
                        $files->get(app_path('Providers/NovaServiceProvider.php'))
                    )
                );
            }

            return;
        }

        if (Str::contains($appConfig, "{$namespace}\\Providers\\NovaServiceProvider::class")) {
            return;
        }

        $files->put(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class,".$eol,
            "{$namespace}\\Providers\EventServiceProvider::class,".$eol."        {$namespace}\Providers\NovaServiceProvider::class,".$eol,
            $appConfig
        ));
    }

    /**
     * Set the proper application namespace on the installed files.
     *
     * @return void
     */
    protected function setAppNamespace(Filesystem $files)
    {
        $namespace = $this->laravel->getNamespace();

        $this->setAppNamespaceOn($files, app_path('Nova/User.php'), $namespace);
        $this->setAppNamespaceOn($files, app_path('Providers/NovaServiceProvider.php'), $namespace);
    }

    /**
     * Set the namespace on the given file.
     *
     * @param  string  $file
     * @param  string  $namespace
     * @return void
     */
    protected function setAppNamespaceOn(Filesystem $files, $file, $namespace)
    {
        $files->put($file, str_replace(
            'App\\',
            $namespace,
            $files->get($file)
        ));
    }
}
