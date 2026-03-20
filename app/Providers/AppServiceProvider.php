<?php

namespace App\Providers;

use App\Channels\SmsNotifyChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use App\Services\MenuService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // The log will be used in the Notification's via method
        // You can use whatever name your want
        Notification::extend('smsNotify', function ($app) {
            return new SmsNotifyChannel();
        });

        // Scribe (API 文档) 仅在开发环境加载，已在 composer.json dont-discover 中禁用自动发现
        if ($this->app->environment('local') && class_exists(\Knuckles\Scribe\ScribeServiceProvider::class)) {
            $this->app->register(\Knuckles\Scribe\ScribeServiceProvider::class);
        }
    }


    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        // 共享语言数据到所有视图
        View::share('availableLocales', config('app.available_locales'));
        // 或者只共享到特定视图
        View::composer('*', function ($view) {
            $view->with('availableLocales', config('app.available_locales'));
        });

        // Migration 完成后自动清除菜单缓存
        Event::listen(MigrationsEnded::class, function () {
            app(MenuService::class)->clearAllCache();
        });

        // 动态菜单数据注入
        View::composer('partials.sidebar-dynamic', function ($view) {
            if (Auth::check()) {
                $view->with('menuTree', app(MenuService::class)->getMenuTreeForUser(Auth::user()));
            } else {
                $view->with('menuTree', collect());
            }
        });
    }
}
