<?php

namespace App\Providers;

use App\Channels\SmsNotifyChannel;
use Carbon\Carbon;
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

        // Carbon 实例被直接交给 json_encode 时（例如把模型的日期属性放进数组再
        // response()->json()，此路径走 getAttribute() 返回 Carbon 本体，不经过
        // 模型 cast 的格式化），默认输出 ISO-8601 UTC，如
        // 2026-07-31T16:00:00.000000Z。统一改为应用时区下的 'Y-m-d H:i:s'，
        // 与模型 cast 的序列化格式对齐，确保接口里不再出现 UTC 时间。
        //
        // 注：纯日期字段经此路径会带出 00:00:00，展示层应显式 ->format('Y-m-d')；
        // 本设置的作用是兜底，保证任何遗漏处至少不是 UTC。
        Carbon::serializeUsing(fn ($date) => $date->format('Y-m-d H:i:s'));
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
