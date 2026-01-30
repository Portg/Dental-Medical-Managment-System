<?php

namespace App\Http\Helper;

class LocaleHelper
{
    public static function getAvailableLocales()
    {
        return config('app.available_locales', ['en' => 'English']);
    }

    public static function getCurrentLocale()
    {
        return app()->getLocale();
    }

    public static function getCurrentLocaleName()
    {
        $locales = self::getAvailableLocales();
        $current = self::getCurrentLocale();
        return $locales[$current] ?? 'English';
    }
}