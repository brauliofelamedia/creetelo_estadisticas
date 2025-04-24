<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use \Barryvdh\Debugbar\Facades\Debugbar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Debugbar', Debugbar::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        if (Schema::hasTable('configs')) {
            View::share('config_global', Config::first());
        }
    }
}
