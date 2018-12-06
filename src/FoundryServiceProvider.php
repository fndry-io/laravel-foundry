<?php

namespace Foundry;

use Foundry\Config\SettingRepository;
use Foundry\Contracts\Repository;
use Foundry\Models\Setting;
use Foundry\Observers\SettingObserver;
use Foundry\Providers\ConsoleServiceProvider;
use Foundry\Providers\EventServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class FoundryServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Booting the package.
     */
    public function boot()
    {
        Setting::observe(new SettingObserver());

        $this->registerNamespaces();

        if ($this->app->runningInConsole()) {
            $this->registerMigrations();

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'foundry-migrations');
        }
    }

    /**
     * Register all modules.
     */
    public function register()
    {
        $this->registerServices();
        $this->registerProviders();
    }

    /**
     * Register package's namespaces.
     */
    protected function registerNamespaces()
    {
        $moduleConfigPath = __DIR__ . '/../config/modules.php';
        $configPath = __DIR__ . '/../config/config.php';

        $this->mergeConfigFrom($moduleConfigPath, 'modules');
        $this->mergeConfigFrom($configPath, 'foundry');

        $this->publishes([
            $moduleConfigPath => config_path('modules.php'),
            $configPath => config_path('foundry.php')
        ], 'config');

    }

    /**
     * Register the service provider.
     */
   protected function registerServices()
   {

       $this->app->singleton(Repository::class, function () {

           if (Cache::has('settings')) {
               $settings = Cache::get('settings');
           }else{
               $settings = SettingObserver::getSettingsItems();
               Cache::put('settings', $settings, now()->addDays(30));
           }

           return new SettingRepository($settings);
       });

       $this->app->alias(Repository::class, 'settings');

   }

    /**
     * Get the services provided by the provider.
     *
     */
    public function provides() : void
    {

    }

    /**
     * Register providers.
     */
    protected function registerProviders() : void
    {
        $this->app->register(ConsoleServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register Passport's migration files.
     *
     * @return void
     */
    protected function registerMigrations() : void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

    }
}
