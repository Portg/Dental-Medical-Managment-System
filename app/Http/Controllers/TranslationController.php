<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;

class TranslationController extends Controller
{
    /**
     * 获取指定模块的翻译（支持命名空间）
     *
     * GET /translations/{module}?locale=zh-CN
     *
     * 支持格式:
     * - /translations/patient              -> resources/lang/{locale}/patient.php
     * - /translations/package/messages     -> 表示 package::messages
     * - /translations/vendor/pkg/errors    -> 表示 vendor/pkg::errors
     *
     * @param string $module - 模块名称（URL 中 / 会被转换为 ::）
     */
    public function getModule(Request $request, $module)
    {
        $locale = $this->validateLocale($request->input('locale'));
        App::setLocale($locale);

        // 将 URL 格式转换为 Laravel 命名空间格式
        // vendor/package/messages -> vendor/package::messages
        $laravelKey = $this->convertToLaravelKey($module);

        // 获取翻译
        $translations = Lang::get($laravelKey);

        // 如果翻译不存在（返回原始键），返回空数组
        if ($translations === $laravelKey) {
            $translations = [];
        }

        return response()->json([
            'status' => true,
            'locale' => $locale,
            'module' => $laravelKey,
            'translations' => $translations
        ]);
    }

    /**
     * 获取多个模块的翻译
     *
     * GET /translations?modules=patient,common,package::messages&locale=zh-CN
     */
    public function getModules(Request $request)
    {
        $locale = $this->validateLocale($request->input('locale'));
        $modules = $request->input('modules', '');

        App::setLocale($locale);

        $moduleList = array_filter(array_map('trim', explode(',', $modules)));

        $result = [];
        foreach ($moduleList as $module) {
            // 支持直接使用 :: 格式或 / 格式
            $laravelKey = $this->convertToLaravelKey($module);
            $translations = Lang::get($laravelKey);

            if ($translations !== $laravelKey) {
                $result[$laravelKey] = $translations;
            }
        }

        return response()->json([
            'status' => true,
            'locale' => $locale,
            'translations' => $result
        ]);
    }

    /**
     * 获取所有可用的翻译模块列表（包括命名空间模块）
     *
     * GET /translations/list?locale=zh-CN
     */
    public function listModules(Request $request)
    {
        $locale = $this->validateLocale($request->input('locale'));

        $modules = [];

        // 1. 获取标准语言文件
        $langPath = resource_path('lang/' . $locale);
        if (File::isDirectory($langPath)) {
            $files = File::files($langPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $modules[] = $file->getFilenameWithoutExtension();
                }
            }
        }

        // 2. 获取 vendor 命名空间模块
        $vendorPath = resource_path('lang/vendor');
        if (File::isDirectory($vendorPath)) {
            $packages = File::directories($vendorPath);
            foreach ($packages as $packagePath) {
                $packageName = basename($packagePath);
                $packageLangPath = $packagePath . '/' . $locale;

                if (File::isDirectory($packageLangPath)) {
                    $files = File::files($packageLangPath);
                    foreach ($files as $file) {
                        if ($file->getExtension() === 'php') {
                            $moduleName = $file->getFilenameWithoutExtension();
                            $modules[] = $packageName . '::' . $moduleName;
                        }
                    }
                }
            }
        }

        return response()->json([
            'status' => true,
            'locale' => $locale,
            'modules' => $modules
        ]);
    }

    /**
     * 获取全部翻译（包括命名空间模块）
     *
     * GET /translations/all?locale=zh-CN
     */
    public function getAll(Request $request)
    {
        $locale = $this->validateLocale($request->input('locale'));
        App::setLocale($locale);

        $allTranslations = [];

        // 1. 加载标准语言文件
        $langPath = resource_path('lang/' . $locale);
        if (File::isDirectory($langPath)) {
            $files = File::files($langPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $module = $file->getFilenameWithoutExtension();
                    $translations = Lang::get($module);
                    if ($translations !== $module) {
                        $allTranslations[$module] = $translations;
                    }
                }
            }
        }

        // 2. 加载 vendor 命名空间模块
        $vendorPath = resource_path('lang/vendor');
        if (File::isDirectory($vendorPath)) {
            $packages = File::directories($vendorPath);
            foreach ($packages as $packagePath) {
                $packageName = basename($packagePath);
                $packageLangPath = $packagePath . '/' . $locale;

                if (File::isDirectory($packageLangPath)) {
                    $files = File::files($packageLangPath);
                    foreach ($files as $file) {
                        if ($file->getExtension() === 'php') {
                            $moduleName = $file->getFilenameWithoutExtension();
                            $fullKey = $packageName . '::' . $moduleName;
                            $translations = Lang::get($fullKey);
                            if ($translations !== $fullKey) {
                                $allTranslations[$fullKey] = $translations;
                            }
                        }
                    }
                }
            }
        }

        return response()->json([
            'status' => true,
            'locale' => $locale,
            'translations' => $allTranslations
        ]);
    }

    /**
     * 验证并返回有效的语言代码
     */
    protected function validateLocale($locale)
    {
        $locale = $locale ?: App::getLocale();

        $availableLocales = config('app.available_locales', ['en' => 'English', 'zh-CN' => '简体中文']);

        // 处理关联数组
        if (is_array($availableLocales) && !isset($availableLocales[0])) {
            $availableLocales = array_keys($availableLocales);
        }

        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.locale', 'zh-CN');
        }

        return $locale;
    }

    /**
     * 将 URL 格式转换为 Laravel 命名空间格式
     *
     * vendor/package/messages -> vendor/package::messages
     * package/messages        -> package::messages
     * patient                 -> patient
     */
    protected function convertToLaravelKey($urlPath)
    {
        // 如果已经包含 ::，直接返回
        if (strpos($urlPath, '::') !== false) {
            return $urlPath;
        }

        // 如果包含 /，转换为命名空间格式
        if (strpos($urlPath, '/') !== false) {
            $lastSlash = strrpos($urlPath, '/');
            $namespace = substr($urlPath, 0, $lastSlash);
            $module = substr($urlPath, $lastSlash + 1);
            return $namespace . '::' . $module;
        }

        return $urlPath;
    }
}