<?php

namespace Modules\Addons\PrivateNsRegistrar\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class PrivateNsRegistrarServiceProvider extends ServiceProvider
{
  protected $moduleName = 'PrivateNsRegistrar';
  protected $moduleNameLower = 'privatensregistrar';

  public function boot()
  {
    $this->registerTranslations();
    $this->registerConfig();
    $this->registerViews();
    $this->registerRoutes();
    $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

    // Add asset publishing
    $this->publishes([
      module_path($this->moduleName, 'Resources/assets/css') => public_path('vendor/privatensregistrar/css'),
      module_path($this->moduleName, 'Resources/assets/js') => public_path('vendor/privatensregistrar/js'),
    ], 'public');
  }


  public function register()
  {
    $this->app->register(RouteServiceProvider::class);
  }

  protected function registerConfig()
  {
    $this->publishes([
      module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
    ], 'config');
    $this->mergeConfigFrom(
      module_path($this->moduleName, 'Config/config.php'),
      $this->moduleNameLower
    );
  }

  protected function registerViews()
  {
    $sourcePath = module_path($this->moduleName, 'Resources/views');
    $this->loadViewsFrom($sourcePath, 'privatensregistrar');
  }

  protected function registerTranslations()
  {
    $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

    if (is_dir($langPath)) {
      $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
    } else {
      $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
    }
  }

  // protected function registerRoutes()
  // {
  //     Route::middleware('web')
  //         ->namespace('Modules\Addons\PrivateNsRegistrar\Http\Controllers')
  //         ->group(function () {
  //             Route::get('/privatensregistrar', 'PrivateNsRegistrarController@output')->name('privatens_registrar.index');
  //             Route::post('/privatensregistrar/syncTLD', 'PrivateNsRegistrarController@syncTLD')->name('privatens_registrar.syncTLD');
  //             Route::post('/privatensregistrar/domain-document', 'PrivateNsRegistrarController@fetchDomainDocument')->name('privatens_registrar.domain_document');
  //             Route::post('/privatensregistrar/process-doc', 'PrivateNsRegistrarController@processDocument')->name('privatens_registrar.process_document');

  //             // Add route for handling "Detail"
  //             Route::get('/privatensregistrar/document-client', 'PrivateNsRegistrarController@handleDocumentClientPage')
  //                 ->name('privatens_registrar.document_client');
  //         });
  // }
  protected function registerRoutes()
  {
    Route::middleware('web')
      ->namespace('Modules\Addons\PrivateNsRegistrar\Http\Controllers')
      ->group(function () {
        Route::get('/privatensregistrar', 'PrivateNsRegistrarController@output')->name('privatens_registrar.index');
        Route::post('/privatensregistrar/syncTLD', 'PrivateNsRegistrarController@syncTLD')->name('privatens_registrar.syncTLD');
        Route::post('/privatensregistrar/domain-document', 'PrivateNsRegistrarController@fetchDomainDocument')->name('privatens_registrar.domain_document');
        Route::post('/privatensregistrar/process-doc', 'PrivateNsRegistrarController@processDocument')->name('privatens_registrar.process_document');
      });
  }


  private function getPublishableViewPaths(): array
  {
    $paths = [];
    foreach (\Config::get('view.paths') as $path) {
      if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
        $paths[] = $path . '/modules/' . $this->moduleNameLower;
      }
    }
    return $paths;
  }
}
