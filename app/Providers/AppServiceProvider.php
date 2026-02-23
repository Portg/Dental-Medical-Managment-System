<?php

namespace App\Providers;

use App\Channels\SmsNotifyChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use App\Services\MenuService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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
