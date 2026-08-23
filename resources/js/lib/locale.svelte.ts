import { page } from '@inertiajs/svelte';

/**
 * The locale every other locale falls back to, mirroring the server's own fallback.
 */
export const DEFAULT_LOCALE = 'zh_TW';

export function availableLocales(): string[] {
    return page.props.available_locales;
}

/**
 * The single answer to "what language are we in".
 *
 * Resolved directly from the URL-based Inertia page props (e.g. 'zh_TW', 'zh_CN', 'ja').
 */
export function currentLocale(): string {
    return page.props.locale || DEFAULT_LOCALE;
}

/**
 * Converts an internal locale ('zh_TW') into a URL route key ('zh-tw').
 */
export function toLocaleUrlKey(locale: string): string {
    return locale.toLowerCase().replace('_', '-');
}

/**
 * The current URL route key ('zh-tw', 'zh-cn', 'ja').
 */
export function currentLocaleUrlKey(): string {
    return toLocaleUrlKey(currentLocale());
}

/**
 * Interface copy for the current locale.
 *
 * The server ships every locale and this picks one, so switching needs no request. Falls back
 * to zh_TW the way the vocabulary resolution does, and to an empty map if copy is absent
 * altogether — every call site already guards its own lookups with an inline literal.
 */
export function translations(): Record<string, string> {
    const all = page.props.translations?.app || {};

    return all[currentLocale()] || all[DEFAULT_LOCALE] || {};
}

/**
 * Replaces Laravel-style `:key` placeholders in a translated string.
 */
export function interpolate(template: string, replacements: Record<string, string> = {}): string {
    for (const [key, value] of Object.entries(replacements)) {
        template = template.replace(`:${key}`, value);
    }

    return template;
}

/**
 * The row to display from a record's stored translations.
 *
 * Content the learner is here to read — level names, vocabulary — is translated in the database
 * rather than in the interface copy, so every page that shows it needs the same fallback chain.
 */
export function localized<T>(translations: Record<string, T> | undefined | null): T | undefined {
    if (!translations) {
        return undefined;
    }

    return (
        translations[currentLocale()] ||
        translations[DEFAULT_LOCALE] ||
        Object.values(translations)[0]
    );
}
