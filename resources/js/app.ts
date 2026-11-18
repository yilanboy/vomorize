import { createInertiaApp } from '@inertiajs/svelte';
import AppLayout from '@/layouts/AppLayout.svelte';
import AuthLayout from '@/layouts/AuthLayout.svelte';
import SettingsLayout from '@/layouts/settings/Layout.svelte';
import { initializeFlashToast } from '@/lib/flashToast';
import { learningProgressDb } from '@/lib/learningProgressDb';
import { initializeProgressSync } from '@/lib/progressSync';
import { initializeTheme } from '@/lib/theme.svelte';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const authPages = new Set([
    'login/Page',
    'register/Page',
    'forgot-password/Page',
    'reset-password/Page',
    'verify-email/Page',
    'confirm-password/Page',
    'two-factor-challenge/Page',
]);

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case authPages.has(name):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// This will automatically sync guest IndexedDB progress upon login or registration...
initializeProgressSync();

// Expose the guest progress store so browser tests can seed and inspect IndexedDB
// through the same API the app uses, instead of duplicating the schema.
if (typeof window !== 'undefined') {
    window.__learningProgressDb = learningProgressDb;
}
