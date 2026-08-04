import { page } from '@inertiajs/svelte';

/**
 * The locale every other locale falls back to, mirroring the server's own fallback.
 */
export const DEFAULT_LOCALE = 'zh_TW';

export const SUPPORTED_LOCALES = ['zh_TW', 'zh_CN', 'ja'] as const;

/**
 * The locale the learner picked in this browser, if they have picked one.
 *
 * Module scope, so it outlives navigation the way the learner's intent does. Safe under
 * server-side rendering for one reason worth stating: it is only ever written from a click
 * handler. Module state is shared across concurrent renders in a server process, so a write
 * during a render could leak one visitor's language into another's page — a read cannot.
 *
 * The browser value takes precedence over the server's first-request negotiation after the app
 * loads, so a learner who switches and immediately taps a link keeps the selected language.
 */
const chosen = $state<{ value: string | null }>({ value: null });

/**
 * Restores a locale chosen on an earlier visit. The server cannot read local storage, so the
 * initial request uses Accept-Language and this client-side value takes over after the app loads.
 */
export function initializeLocale(): void {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        const stored = localStorage.getItem('locale');

        if (stored && SUPPORTED_LOCALES.includes(stored as (typeof SUPPORTED_LOCALES)[number])) {
            chosen.value = stored;
        }
    } catch {
        // Storage may be unavailable in restricted browser contexts.
    }
}

/**
 * The single answer to "what language are we in".
 *
 * Falls back to the locale the server resolved, so server-rendered markup and the first client
 * render agree by construction — which is why no pre-paint script is needed here. Unlike the
 * theme, where appearance is a class that can be corrected before paint, a locale is the content
 * itself and is already in the markup, so a client-side correction would be a visible repaint.
 */
export function currentLocale(): string {
    return chosen.value || page.props.locale || DEFAULT_LOCALE;
}

/**
 * Switches language for this tab. Every locale is already in the browser, so there is nothing
 * to fetch and nothing to re-render — which is what keeps a learner's place in a quiz.
 */
export function setLocale(code: string): void {
    chosen.value = code;

    if (typeof window !== 'undefined') {
        localStorage.setItem('locale', code);
    }
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
 * Resolving it here rather than in the page keeps a language switch working: the server is never
 * asked again, so a row the server picked during the render would stay in the arrival language.
 */
export function localized<T extends { locale: string }>(rows: T[] | undefined): T | undefined {
    return (
        rows?.find((row) => row.locale === currentLocale()) ??
        rows?.find((row) => row.locale === DEFAULT_LOCALE) ??
        rows?.[0]
    );
}
