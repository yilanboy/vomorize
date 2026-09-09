import { page } from '@inertiajs/svelte';

/**
 * 輕量級多語系翻譯輔助函式
 * 對接 Inertia page.props.translations 字典並支援參數替換
 *
 * @example
 * t('ui.nav.levels') -> "等級列表"
 * t('ui.footer.copyright', { year: 2026 }) -> "© 2026 Vomorize..."
 */
export function t(
    key: string,
    replacements: Record<string, string | number> = {},
): string {
    const props = page.props as Record<string, any>;
    const translations = props?.translations ?? {};

    const segments = key.split('.');
    let current: any = translations;

    for (const segment of segments) {
        if (current && typeof current === 'object' && segment in current) {
            current = current[segment];
        } else {
            current = undefined;
            break;
        }
    }

    if (typeof current !== 'string') {
        return key;
    }

    let result = current;
    for (const [placeholder, value] of Object.entries(replacements)) {
        result = result.replace(
            new RegExp(`:${placeholder}`, 'g'),
            String(value),
        );
    }

    return result;
}
