<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{

    public function handle($request, Closure $next)
    {
        // 从 session 获取语言设置
        $locale = Session::get('locale', config('app.locale', 'en'));
        // 如果 session 中没有设置语言，则从浏览器获取首选语言
        if (empty($locale)) {
            // 从浏览器获取首选语言
            $locale = $request->getPreferredLanguage(['en', 'zh-CN']);
        }

        // 设置应用的语言环境
        App::setLocale($locale);

        return $next($request);
    }
}
