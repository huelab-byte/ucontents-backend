<?php

namespace Modules\StorageManagement\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class StorageManagementServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'StorageManagement';

    protected string $nameLower = 'storagemanagement';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerPolicies();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register policies for the module.
     */
    protected function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\StorageManagement\Models\StorageSetting::class,
            \Modules\StorageManagement\Policies\StorageSettingPolicy::class
        );
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Load providers manually to avoid autoload issues during package discovery
        if (class_exists(\Modules\StorageManagement\Providers\EventServiceProvider::class)) {
            $this->app->register(\Modules\StorageManagement\Providers\EventServiceProvider::class);
        }
        if (class_exists(\Modules\StorageManagement\Providers\RouteServiceProvider::class)) {
            $this->app->register(\Modules\StorageManagement\Providers\RouteServiceProvider::class);
        }
        
        // Register Storage Management Service as singleton
        if (class_exists(\Modules\StorageManagement\Services\StorageManagementService::class)) {
            $this->app->singleton('storage.management', function ($app) {
                return new \Modules\StorageManagement\Services\StorageManagementService(
                    $app->make(\Modules\StorageManagement\Actions\CreateStorageConfigAction::class),
                    $app->make(\Modules\StorageManagement\Actions\UpdateStorageConfigAction::class),
                    $app->make(\Modules\StorageManagement\Actions\MigrateStorageAction::class)
                );
            });
        }
        
        // Register File Upload Service as singleton
        if (class_exists(\Modules\StorageManagement\Services\FileUploadService::class)) {
            $this->app->singleton('storage.upload', function ($app) {
                return new \Modules\StorageManagement\Services\FileUploadService(
                    $app->make(\Modules\StorageManagement\Actions\UploadFileAction::class)
                );
            });
        }
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
