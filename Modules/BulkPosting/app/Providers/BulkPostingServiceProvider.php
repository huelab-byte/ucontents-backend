<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\BulkPosting\Models\BulkPostingCampaign;
use Modules\BulkPosting\Policies\BulkPostingCampaignPolicy;

class BulkPostingServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'BulkPosting';

    protected string $moduleNameLower = 'bulkposting';

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerPolicies();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(BulkPostingCampaign::class, BulkPostingCampaignPolicy::class);
    }

    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\BulkPosting\Console\ProcessBulkPostingScheduleCommand::class,
        ]);
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            // Requires: php artisan schedule:work (dev) or cron "* * * * * php artisan schedule:run" (prod)
            $schedule->command('bulk-posting:process-schedule')->everyMinute();
        });
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $this->publishes([module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower . '.php')], 'config');
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower);
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/module.php'), $this->moduleNameLower . '.module');
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->moduleNameLower . '-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }

        return $paths;
    }
}
