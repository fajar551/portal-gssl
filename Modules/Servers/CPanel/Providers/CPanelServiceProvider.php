<?php

namespace Modules\Servers\CPanel\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Servers\CPanel\Services\CpanelService;
use Modules\Servers\CPanel\Http\Controllers\CPanelController;

class CPanelServiceProvider extends ServiceProvider
{
    protected $moduleName = 'CPanel';

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        
        $this->app->singleton(CpanelService::class, function ($app) {
            return new CpanelService();
        });

        $this->app->bind(CPanelController::class, function($app) {
            return new CPanelController($app->make(CpanelService::class));
        });
    }

    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
    }

    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path('cpanel.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), 'cpanel'
        );
    }

    protected function registerViews()
    {
        $viewPath = resource_path('views/modules/' . strtolower($this->moduleName));

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/' . strtolower($this->moduleName);
        }, \Config::get('view.paths')), [$sourcePath]), strtolower($this->moduleName));
    }
}