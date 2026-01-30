<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{

    /**
     * 获取支持的语言列表
     *
     * @return array
     */
    protected function getAvailableLocales()
    {
        $locales = config('app.available_locales', ['en' => 'English', 'zh-CN' => '简体中文']);

        // 如果是关联数组 ['en' => 'English']，返回键（语言代码）
        // 如果是索引数组 ['en', 'zh-CN']，直接返回
        if ($this->isAssociativeArray($locales)) {
            return array_keys($locales);
        }

        return $locales;
    }

    /**
     * 检查是否为关联数组
     */
    protected function isAssociativeArray(array $arr)
    {
        if (empty($arr)) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * 标准化语言代码
     * 处理简写形式：zh -> zh-CN, en -> en
     *
     * @param  string  $locale
     * @param  array   $availableLocales
     * @return string
     */
    protected function normalizeLocale($locale, array $availableLocales)
    {
        // 如果已经是有效的语言代码，直接返回
        if (in_array($locale, $availableLocales)) {
            return $locale;
        }

        // 语言代码映射表（简写 -> 完整代码）
        $localeMap = [
            'zh' => 'zh-CN',      // 中文简写 -> 简体中文
            'zh-cn' => 'zh-CN',   // 小写形式
            'zh-tw' => 'zh-TW',   // 繁体中文
            'zh-hk' => 'zh-TW',   // 香港 -> 繁体
            'en-us' => 'en',      // 美式英语
            'en-gb' => 'en',      // 英式英语
        ];

        $localeLower = strtolower($locale);

        // 检查映射表
        if (isset($localeMap[$localeLower])) {
            $mapped = $localeMap[$localeLower];
            if (in_array($mapped, $availableLocales)) {
                Log::info("Locale mapped: {$locale} -> {$mapped}");
                return $mapped;
            }
        }

        // 尝试前缀匹配（如 en-US 匹配 en）
        $prefix = explode('-', $locale)[0];
        foreach ($availableLocales as $available) {
            if (strpos($available, $prefix) === 0 || $available === $prefix) {
                return $available;
            }
        }

        // 返回原值
        return $locale;
    }

    /**
     * 切换语言（GET 请求）
     *
     * 路由名称: language.switch
     * URL: /language/{locale}
     *
     * @param  string  $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch($locale)
    {
        // 获取配置文件中的可用语言
        $availableLocales = $this->getAvailableLocales();
        // 处理语言代码映射（zh -> zh-CN）
        $locale = $this->normalizeLocale($locale, $availableLocales);

        if (in_array($locale, $availableLocales)) {
            Session::put('locale', $locale);
            App::setLocale($locale);
        } else {
            Log::warning('Unsupported locale: ' . $locale);
        }

        return redirect()->back();
    }

    /**
     * 设置语言（POST 请求，用于 AJAX）
     *
     * 路由名称: language.set 或 locale.set
     * URL: /set-locale
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setLocale(Request $request)
    {
        $locale = $request->input('locale');

        $availableLocales = $this->getAvailableLocales();

        // 处理语言代码映射
        $locale = $this->normalizeLocale($locale, $availableLocales);

        if (in_array($locale, $availableLocales)) {
            Session::put('locale', $locale);
            App::setLocale($locale);

            return response()->json([
                'status' => true,
                'message' => __('language.language_updated_successfully'),
                'locale' => $locale
            ]);
        }

        Log::warning('Unsupported locale: ' . $locale);

        return response()->json([
            'status' => false,
            'message' => __('language.unsupported_language', ['locale' => $locale]),
            'available' => $availableLocales
        ], 400);
    }

    /**
     * 获取当前语言信息（API）
     *
     * 路由: GET /current-locale
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentLocale()
    {
        return response()->json([
            'current' => App::getLocale(),
            'available' => config('app.available_locales', [])
        ]);
    }
}