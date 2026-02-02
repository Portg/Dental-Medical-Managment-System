<?php

namespace Modules\Pharmacy\Providers;

use Illuminate\Support\ServiceProvider;

class PharmacyServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path('Pharmacy', 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path('Pharmacy', 'Config/config.php') => config_path('pharmacy.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('Pharmacy', 'Config/config.php'), 'pharmacy'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/pharmacy');

        $sourcePath = module_path('Pharmacy', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/pharmacy';
        }, \Config::get('view.paths')), [$sourcePath]), 'pharmacy');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/pharmacy');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'pharmacy');
        } else {
            $this->loadTranslationsFrom(module_path('Pharmacy', 'Resources/lang'), 'pharmacy');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
