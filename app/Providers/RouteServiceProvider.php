<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        // Default API rate limit: 120/min for authenticated users, 60/min for guests
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(60)->by($request->ip());
        });

        // Strict rate limit for login endpoint: 5 attempts/min per IP
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many login attempts. Please try again later.',
                    ], 429);
                });
        });

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapApiV1Routes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    /**
     * API v1 路由统一挂 api.v1. 名字前缀。
     *
     * 不加前缀的话，api/v1 里的 Route::apiResource('patients', ...) 生成的名字是
     * patients.index，和 web.php 里 Route::resource('patients', ...) 撞车 —— 全站
     * 有 68 个这样的重名（14 个资源 × 各 CRUD 动作）。
     *
     * 不缓存路由时 Laravel 容忍重名（后注册的覆盖），所以一直没暴露；但
     * `artisan route:cache` 会直接拒绝序列化并以退出码 1 失败，导致**生产环境
     * 从来没有过路由缓存**，每个请求都要重新注册全部路由。同时 route('patients.index')
     * 解析到哪一条取决于注册顺序，URL 生成是有歧义的。
     *
     * 改 API 侧而不是 Web 侧：API 消费方用的是 URL（/api/v1/patients），不走
     * 路由名；Web 侧那些才是 Blade 里将来会用到的常规名字。已核对全仓库，
     * 这 68 个名字当前零引用（无 route() 调用、无 Ziggy、无动态调用），改名安全。
     */
    protected function mapApiV1Routes()
    {
        Route::prefix('api/v1')
             ->name('api.v1.')
             ->middleware(['api', 'auth:sanctum', 'api.version:v1'])
             ->namespace($this->namespace . '\Api\V1')
             ->group(base_path('routes/api/v1.php'));
    }
}
