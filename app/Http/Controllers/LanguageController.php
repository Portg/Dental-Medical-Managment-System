<?php

namespace App\Http\Controllers;

use App\Services\LanguageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    private LanguageService $languageService;

    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
        $this->middleware('can:manage-settings');
    }

    /**
     * Switch language (GET request).
     *
     * @param string $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch($locale)
    {
        $resolved = $this->languageService->resolve($locale);

        if ($resolved !== null) {
            Session::put('locale', $resolved);
            App::setLocale($resolved);
        }

        return redirect()->back();
    }

    /**
     * Set language (POST request, for AJAX).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setLocale(Request $request)
    {
        $locale = $request->input('locale');
        $resolved = $this->languageService->resolve($locale);

        if ($resolved !== null) {
            Session::put('locale', $resolved);
            App::setLocale($resolved);

            return response()->json([
                'status' => true,
                'message' => __('language.language_updated_successfully'),
                'locale' => $resolved,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => __('language.unsupported_language', ['locale' => $locale]),
            'available' => $this->languageService->getAvailableLocales(),
        ], 400);
    }

    /**
     * Get current locale info (API).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentLocale()
    {
        return response()->json([
            'current' => App::getLocale(),
            'available' => config('app.available_locales', []),
        ]);
    }
}
