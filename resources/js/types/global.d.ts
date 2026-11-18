import type { learningProgressDb } from '@/lib/learningProgressDb';
import type { SupportedLocale } from '@/lib/locale';
import type { Auth } from '@/types/auth';

declare global {
    interface Window {
        // Exposed in app.ts for browser tests to seed / inspect guest progress.
        __learningProgressDb?: typeof learningProgressDb;
    }
}

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(
            pattern: string,
            options?: { eager?: boolean },
        ) => Record<string, T>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: SupportedLocale;
            vocabularyPerGroup: number;
            [key: string]: unknown;
        };
    }
}
