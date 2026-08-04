<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'locale' => $locale,
            'available_locales' => [
                'zh_TW' => '繁體中文',
                'zh_CN' => '简体中文',
                'ja' => '日本語',
            ],
            /**
             * Every supported locale, not just the resolved one, so the client can switch
             * language without a round trip. Iterating the list that already gates the switch
             * endpoint's validation keeps the two from disagreeing about what is switchable.
             */
            'translations' => [
                'app' => collect(SetLocale::SUPPORTED_LOCALES)
                    ->mapWithKeys(fn (string $supported): array => [
                        $supported => trans('app', [], $supported),
                    ])
                    ->all(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
