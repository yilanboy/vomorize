import { router } from '@inertiajs/svelte';
import { store as syncProgress } from '@/actions/App/Http/Controllers/ProgressSyncController';
import { learningProgressDb } from '@/lib/learningProgressDb';

let isSyncing = false;

async function syncGuestProgressIfPresent(): Promise<void> {
    if (isSyncing) {
        return;
    }

    isSyncing = true;

    try {
        const records = await learningProgressDb.getAllProgress();
        if (records.length === 0) {
            isSyncing = false;
            return;
        }

        router.post(
            syncProgress.url(),
            {
                local_progress: records.map((record) => ({
                    group_id: record.group_id,
                    level_id: record.level_id,
                    stage: record.stage,
                    last_score: record.last_score,
                    last_reviewed_at: record.last_reviewed_at,
                    next_review_at: record.next_review_at,
                })),
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    void learningProgressDb.clearAllProgress();
                },
                onFinish: () => {
                    isSyncing = false;
                },
            },
        );
    } catch {
        // Keep local IndexedDB records intact on network failure so next navigation retries
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
