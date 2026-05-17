<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
      Schema::defaultStringLength(191);
      Paginator::useBootstrap();

      // Performance Optimizer addon — auto-purge listeners
      if (class_exists(\App\Providers\PerformanceOptimizerEventServiceProvider::class)) {
          $this->app->register(\App\Providers\PerformanceOptimizerEventServiceProvider::class);
      }
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    //
  }
}
