<?php

namespace Modules\Authorization\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Modules\Authorization\Services\AuthorizationService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AuthorizationServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Authorization';

    protected string $nameLower = 'authorization';

    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Policies will be registered here as needed
    ];

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerGates();
        $this->registerMiddleware();
        $this->registerObservers();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Register the authorization gates.
     */
    protected function registerGates(): void
    {
        Gate::define('auth-object', function ($user, string $objectCode, array $fields = []) {
            // Super-admin completely bypasses all authorization checks
            $authService = app(\App\Contracts\Services\AuthenticationServiceInterface::class);
            if ($authService->isSuperAdmin($user->id)) {
                // Log privileged activity for SuperAdmin (if request is available)
                if (app()->bound('request')) {
                    try {
                        $logger = app(\Modules\Authorization\Services\PrivilegedActivityLogger::class);
                        $logger->log(
                            $user->id,
                            'SuperAdmin',
                            $objectCode,
                            $fields['ACTVT'] ?? null,
                            $fields,
                            request(),
                            'SuperAdmin bypassed authorization check via Gate'
                        );
                    } catch (\Exception $e) {
                        // Silently fail if logging fails
                    }
                }
                
                return true;
            }

            // Super-read-only bypasses authorization for read-only operations
            if ($authService->isSuperReadOnly($user->id)) {
                $activityCode = $fields['ACTVT'] ?? null;
                // If no activity specified or activity is '03' (Display), allow access
                if ($activityCode === null || $activityCode === '03') {
                    // Log privileged activity for SuperReadOnly (if request is available)
                    if (app()->bound('request')) {
                        try {
                            $logger = app(\Modules\Authorization\Services\PrivilegedActivityLogger::class);
                            $logger->log(
                                $user->id,
                                'SuperReadOnly',
                                $objectCode,
                                $activityCode ?? '03',
                                $fields,
                                request(),
                                'SuperReadOnly bypassed authorization check for read-only operation via Gate'
                            );
                        } catch (\Exception $e) {
                            // Silently fail if logging fails
                        }
                    }
                    
                    return true;
                }
            }

            // Check authorization for non-super-admin and non-super-read-only users
            $authorizationService = app(AuthorizationService::class);
            return $authorizationService->check($user->id, $objectCode, $fields);
        });

        // Define a super-admin gate for convenience
        Gate::define('super-admin', function ($user) {
            $authService = app(\App\Contracts\Services\AuthenticationServiceInterface::class);
            return $authService->isSuperAdmin($user->id);
        });
    }

    /**
     * Register middleware aliases.
     */
    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('auth.object', \Modules\Authorization\Http\Middleware\AuthObjectMiddleware::class);
    }

    /**
     * Register observers for authorization models.
     */
    protected function registerObservers(): void
    {
        $observer = \Modules\Authorization\Observers\AuthorizationCacheObserver::class;

        // Observe RoleAuthorization changes
        \Modules\Authorization\Entities\RoleAuthorization::observe($observer);

        // Observe RoleAuthorizationField changes
        \Modules\Authorization\Entities\RoleAuthorizationField::observe($observer);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}

