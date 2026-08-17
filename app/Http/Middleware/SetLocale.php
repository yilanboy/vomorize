<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const DEFAULT_LOCALE = 'zh_TW';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeLocale = $request->route('locale');

        if ($routeLocale) {
            $matchedLocale = Locale::fromRouteKey($routeLocale);

            if ($matchedLocale) {
                app()->setLocale($matchedLocale->value);
                URL::defaults(['locale' => $matchedLocale->routeKey()]);
                Cookie::queue(Cookie::make('locale', $matchedLocale->routeKey(), 525600, '/', null, null, false));
                $request->route()?->forgetParameter('locale');

                return $next($request);
            }
        }

        // Fallback for unlocalized routes or if route parameter is missing
        $cookieLocale = $request->cookie('locale');
        $matchedFromCookie = Locale::fromRouteKey($cookieLocale);

        if ($matchedFromCookie) {
            app()->setLocale($matchedFromCookie->value);
            URL::defaults(['locale' => $matchedFromCookie->routeKey()]);
        } else {
            app()->setLocale(self::DEFAULT_LOCALE);
            URL::defaults(['locale' => 'zh-tw']);
        }

        return $next($request);
    }
}
