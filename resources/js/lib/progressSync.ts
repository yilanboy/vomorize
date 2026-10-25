import { router, useHttp } from '@inertiajs/svelte';
import { toast } from 'svelte-sonner';
import { store as syncProgress } from '@/actions/App/Http/Controllers/ProgressSyncController';
import {
    learningProgressDb,
    type LocalLearningProgress,
} from '@/lib/learningProgressDb';

interface SyncProgressResponse {
    success: boolean;
    synced_count: number;
    message: string;
}

let isSyncing = false;

async function syncGuestProgressIfPresent(): Promise<void> {
    if (isSyncing) {
        return;
    }

    isSyncing = true;

    try {
        const records = await learningProgressDb.getAllProgress();
        if (records.length === 0) {
            return;
        }

        const http = useHttp<
            { local_progress: LocalLearningProgress[] },
            SyncProgressResponse
        >({
            local_progress: records,
        });

        const response = await http.submit(syncProgress());

        if (response?.success) {
            await learningProgressDb.clearAllProgress();

            if (response.synced_count > 0) {
                if (response.message) {
                    toast.success(response.message);
                }

                router.reload();
            }
        }
    } catch {
        // Keep local IndexedDB records intact on network failure so next navigation retries
    } finally {
        isSyncing = false;
    }
}

export function initializeProgressSync(): void {
    router.on('navigate', (event) => {
        const user = event.detail.page.props?.auth?.user;
        if (user) {
            void syncGuestProgressIfPresent();
        }
    });
}
