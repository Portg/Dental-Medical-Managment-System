<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    private TranslationService $service;

    public function __construct(TranslationService $service)
    {
        $this->service = $service;
    }

    /**
     * Get translations for a specific module (supports namespaces).
     *
     * GET /translations/{module}?locale=zh-CN
     *
     * @param string $module
     */
    public function getModule(Request $request, $module)
    {
        $locale = $this->service->validateLocale($request->input('locale'));
        $result = $this->service->getModuleTranslations($module, $locale);

        return response()->json([
            'status' => true,
            'locale' => $result['locale'],
            'module' => $result['module'],
            'translations' => $result['translations'],
        ]);
    }

    /**
     * Get translations for multiple modules.
     *
     * GET /translations?modules=patient,common,package::messages&locale=zh-CN
     */
    public function getModules(Request $request)
    {
        $locale = $this->service->validateLocale($request->input('locale'));
        $modules = $request->input('modules', '');

        $result = $this->service->getMultipleModuleTranslations($modules, $locale);

        return response()->json([
            'status' => true,
            'locale' => $result['locale'],
            'translations' => $result['translations'],
        ]);
    }

    /**
     * List all available translation modules for a locale.
     *
     * GET /translations/list?locale=zh-CN
     */
    public function listModules(Request $request)
    {
        $locale = $this->service->validateLocale($request->input('locale'));
        $result = $this->service->listAvailableModules($locale);

        return response()->json([
            'status' => true,
            'locale' => $result['locale'],
            'modules' => $result['modules'],
        ]);
    }

    /**
     * Get all translations (including vendor namespace modules).
     *
     * GET /translations/all?locale=zh-CN
     */
    public function getAll(Request $request)
    {
        $locale = $this->service->validateLocale($request->input('locale'));
        $result = $this->service->getAllTranslations($locale);

        return response()->json([
            'status' => true,
            'locale' => $result['locale'],
            'translations' => $result['translations'],
        ]);
    }
}
