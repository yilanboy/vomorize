<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookieLocale = $request->cookie('locale');

        if (is_string($cookieLocale) && Locale::isValid($cookieLocale)) {
            app()->setLocale($cookieLocale);

            return $next($request);
        }

        $preferred = $request->getPreferredLanguage([
            ...Locale::values(),
            'zh_Hans',
            'zh_SG',
        ]);

        $locale = match ($preferred) {
            'zh_Hans', 'zh_SG' => Locale::ZhCn->value,
            default => $preferred ?? Locale::default()->value,
        };

        app()->setLocale($locale);

        return $next($request);
    }
}
