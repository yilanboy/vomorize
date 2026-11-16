import { router } from '@inertiajs/svelte';

export type SupportedLocale = 'zh_TW' | 'ja' | 'zh_CN';

export const SUPPORTED_LOCALES: readonly SupportedLocale[] = [
    'zh_TW',
    'ja',
    'zh_CN',
] as const;

export function isSupportedLocale(value: unknown): value is SupportedLocale {
    return (
        typeof value === 'string' &&
        (SUPPORTED_LOCALES as readonly string[]).includes(value)
    );
}

export function setLocale(locale: SupportedLocale): void {
    if (typeof document !== 'undefined') {
        const maxAge = 365 * 24 * 60 * 60;
        document.cookie = `locale=${locale};path=/;max-age=${maxAge};SameSite=Lax`;
        document.documentElement.lang = locale.replace('_', '-');
    }

    router.reload();
}
