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

        // 病历分段明细（牙位 + 文字）——  病历编辑页有三个入口
        // （edit / create / createForPatient）都渲染 medical_cases.edit，
        // 在 Controller 里逐个传必定漏一个，漏掉那个入口的分行就全是空的。
        View::composer('medical_cases.edit', function ($view) {
            $data = $view->getData();
            $svc = app(\App\Services\MedicalCaseService::class);
            $view->with('caseItems', $svc->getCaseItemsForEdit($data['case'] ?? null));
            // 诊断段走 diagnoses 表（带 ICD 编码），与 caseItems 分开取
            $view->with('diagnosisRows', $svc->getDiagnosesForEdit($data['case'] ?? null));
            // 就诊次数：新建时看这个患者已有几份病历，编辑时给这份自己的序号
            $view->with('visitSequence', $svc->visitSequence(
                $data['case'] ?? null,
                $data['patient']->id ?? ($data['patient_id'] ?? null)
            ));
        });

        // 病历页的快捷短语侧栏已去掉（改用锚定在字段上的短语面板），
        // 对应的 View Composer 随之删除 —— 它指向的 partial 已经不存在了。

        // 快捷短语管理页的槽位下拉：列出库里已有的槽位，省得医生手打错字
        // 开出一个只有一条的新组。它是自由文本，不是枚举 —— 打新名字照样能开新组。
        View::composer('quick_phrases.create', function ($view) {
            $view->with('existingSlots', \App\QuickPhrase::distinctSlots());
        });

        // 锚定短语面板的数据：[病历字段 => [语义槽位 => [短语, ...]]]。
        //
        // 整份一次性注进页面，而不是每次聚焦发一次 ajax —— 全库 400 多条压成 JSON
        // 约 10KB，一次拿完；医生写病历时光标在字段之间来回跳，每跳一次等一次网络
        // 是最不该有的等待。
        View::composer('medical_cases.edit', function ($view) {
            $view->with(
                'phrasePanel',
                Auth::check() ? \App\QuickPhrase::panelForUser(Auth::id()) : []
            );
        });
    }

}
