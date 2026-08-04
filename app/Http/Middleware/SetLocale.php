<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales.
     */
    public const SUPPORTED_LOCALES = ['zh_TW', 'zh_CN', 'ja'];

    public const DEFAULT_LOCALE = 'zh_TW';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = self::DEFAULT_LOCALE;

        if ($request->user() && $request->user()->locale && in_array($request->user()->locale, self::SUPPORTED_LOCALES, true)) {
            $locale = $request->user()->locale;
        } elseif ($request->session()->has('locale') && in_array($request->session()->get('locale'), self::SUPPORTED_LOCALES, true)) {
            $locale = $request->session()->get('locale');
        } elseif ($request->hasHeader('Accept-Language')) {
            $locale = $this->negotiatedLocale($request);
        }

        app()->setLocale($locale);

        return $next($request);
    }

    /**
     * The best supported match for the languages the visitor's browser asks for.
     *
     * Consulted only once every explicit choice has been ruled out, so a visitor who has
     * chosen is never overridden. The negotiated answer is deliberately not persisted: a
     * first-time visitor needs nothing written on their behalf, and an explicit switch
     * already outranks this branch from then on.
     *
     * Symfony's negotiation reads the script subtag, so the `zh-Hant` and `zh-Hans` forms
     * Apple platforms send resolve correctly rather than falling back on region alone. It
     * returns the head of the candidate list when nothing matches, so the default leads that
     * list — which keeps the no-match answer correct however SUPPORTED_LOCALES is ordered.
     */
    private function negotiatedLocale(Request $request): string
    {
        return $request->getPreferredLanguage([
            self::DEFAULT_LOCALE,
            ...self::SUPPORTED_LOCALES,
        ]) ?? self::DEFAULT_LOCALE;
    }
}
