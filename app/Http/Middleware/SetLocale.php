<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const string DEFAULT_LOCALE = 'zh-tw';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeLocale = $request->route('locale');

        if (in_array($routeLocale, config('app.available_locales'))) {
            app()->setLocale($routeLocale);
            Cookie::queue(Cookie::make(
                name: 'locale',
                value: $routeLocale,
                minutes: 525600,
                path: '/',
            ));
            $request->route()->forgetParameter('locale');
            URL::defaults(['locale' => $routeLocale]);

            return $next($request);
        }

        // Fallback for unlocalized routes or if route parameter is missing
        $cookieLocale = $request->cookie('locale');

        if (in_array($cookieLocale, config('app.available_locales'))) {
            app()->setLocale($cookieLocale);
            URL::defaults(['locale' => $cookieLocale]);
        } else {
            app()->setLocale(self::DEFAULT_LOCALE);
            URL::defaults(['locale' => self::DEFAULT_LOCALE]);
        }

        return $next($request);
    }
}
