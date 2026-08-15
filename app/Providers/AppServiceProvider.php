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

        // 打印单据要嵌中文字体，必须开子集化：中文字体动辄 10-25MB，关着的时候
        // dompdf 会把整份字体塞进 PDF，128M 的 memory_limit 当场爆（报在
        // Cpdf.php 的 file_get_contents 那行，看不出跟字体有关）。开了之后只嵌
        // 实际用到的字形，一页单据从 23MB 降到 16KB。
        //
        // 写在这里而不是 config/dompdf.php：包用的是 mergeConfigFrom，顶层浅合并，
        // 只写一个 options 子键会把包里整个 options 数组（font_dir、chroot、
        // temp_dir…）全替换掉。装字体见 php artisan pdf:install-cjk-font。
        config(['dompdf.options.enable_font_subsetting' => true]);

        // 只在真要生成 PDF 时抬内存下限，不动其他请求。
        //
        // 中文字体的度量缓存（.ufm.json）会被整份 json_decode 成 PHP 数组，字形数
        // 上万时很吃内存：实测嵌入 Arial Unicode（约 5 万字形）渲染一页单据需要
        // 192M 才不炸，128M 的默认值会在 BinaryStream/Cpdf 里抛 OOM —— 报错位置
        // 跟字体毫无关系，很难查。Windows 的微软雅黑约 2.8 万字形，开销约为其一半。
        $this->app->resolving('dompdf.wrapper', fn () => $this->raisePdfMemoryFloor());

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

    /**
     * 把 memory_limit 抬到足以嵌入中文字体的水平（若当前更低）。
     *
     * 只在 dompdf 被解析出来时调用，普通请求不受影响。已是 -1（不限）或本来就
     * 够高时原样不动；ini_set 被主机禁用时静默跳过 —— 抬不动就让原本的 OOM
     * 照常发生，总好过在这里再抛一个新异常。
     */
    private function raisePdfMemoryFloor(): void
    {
        $floor = 256 * 1024 * 1024;
        $current = trim((string) ini_get('memory_limit'));

        if ($current === '' || $current === '-1') {
            return;
        }

        $unit = strtolower(substr($current, -1));
        $value = (int) $current;
        $bytes = match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };

        if ($bytes < $floor) {
            @ini_set('memory_limit', '256M');
        }
    }
}
