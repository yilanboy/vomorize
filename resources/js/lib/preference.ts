export function savePreference(key: string, value: string): void {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        localStorage.setItem(key, value);
    } catch {
        // Ignore storage errors (e.g. private mode or quota exceeded)
    }
}

export function loadPreference(key: string, defaultValue: string): string {
    if (typeof window === 'undefined') {
        return defaultValue;
    }

    try {
        return localStorage.getItem(key) ?? defaultValue;
    } catch {
        return defaultValue;
    }
}
