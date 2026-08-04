import type { Auth } from '@/types/auth';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string, options?: { eager?: boolean }) => Record<string, T>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: string;
            available_locales: Record<string, string>;
            /**
             * Interface copy for every supported locale, keyed by locale.
             *
             * Typed rather than left to the index signature below on purpose. Every consumer
             * used to cast this to a flat string map, and a consumer that kept doing so after
             * the reshape would find nothing, miss every lookup, and silently fall back to its
             * inline zh_TW literal — rendering plausible Traditional Chinese that no test,
             * linter or type check would question. Declaring the real shape turns each such
             * site into a type error instead. Read it through `translations()`.
             */
            translations: { app: Record<string, Record<string, string>> };
            [key: string]: unknown;
        };
    }
}
