<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
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
        $routeLocaleKey = $request->route('locale');
        $cookieLocale = $request->cookie('locale');

        // Route contains a locale parameter
        if (is_string($routeLocaleKey)) {
            $locale = Locale::fromRouteKey($routeLocaleKey);

            if ($locale !== null) {
                app()->setLocale($locale->value);

                $routeKey = $locale->routeKey();

                if ($cookieLocale !== $locale->value) {
                    Cookie::queue(Cookie::make(
                        name: 'locale',
                        value: $locale->value,
                        minutes: 525600,
                        path: '/',
                    ));
                }

                $request->route()->forgetParameter('locale');
                URL::defaults(['locale' => $routeKey]);

                return $next($request);
            }
        }

        // Fallback for unlocalized routes or root path
        $resolvedLocale = Locale::tryFromValueOrRouteKey(is_string($cookieLocale) ? $cookieLocale : null);

        if ($resolvedLocale !== null) {
            app()->setLocale($resolvedLocale->value);
            URL::defaults(['locale' => $resolvedLocale->routeKey()]);
        } else {
            $defaultLocale = Locale::tryFromValueOrRouteKey(config('app.locale')) ?? Locale::ChineseT;
            app()->setLocale($defaultLocale->value);
            URL::defaults(['locale' => $defaultLocale->routeKey()]);
        }

        return $next($request);
    }
}
