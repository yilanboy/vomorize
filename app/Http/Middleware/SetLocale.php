<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeLocale = $request->route('locale');
        // Fallback for unlocalized routes or if route parameter is missing
        $cookieLocale = $request->cookie('locale');

        // There is a locale value in route
        if (in_array($routeLocale, config('app.available_locales'), true)) {
            app()->setLocale($routeLocale);

            if ($cookieLocale !== $routeLocale) {
                Cookie::queue(Cookie::make(
                    name: 'locale',
                    value: $routeLocale,
                    minutes: 525600,
                    path: '/',
                ));
            }

            $request->route()->forgetParameter('locale');
            URL::defaults(['locale' => $routeLocale]);

            return $next($request);
        }

        if (in_array($cookieLocale, config('app.available_locales'))) {
            app()->setLocale($cookieLocale);
            URL::defaults(['locale' => $cookieLocale]);
        } else {
            app()->setLocale(config('app.locale'));
            URL::defaults(['locale' => config('app.locale')]);
        }

        return $next($request);
    }
}
