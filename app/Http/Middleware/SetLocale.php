<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check if locale is stored in session
        if (Session::has('locale')) {
            $locale = Session::get('locale');
        } else {
            // Default to browser's language preference or app default
            $locale = $request->getPreferredLanguage(['en', 'zh']) ?? config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
