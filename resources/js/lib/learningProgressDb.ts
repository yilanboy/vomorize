export type GroupStatusValue =
    | 'not_started'
    | 'cooldown'
    | 'ready'
    | 'completed';

export interface LocalLearningProgress {
    group_id: number;
    level_id: number;
    stage: number;
    last_score: number;
    last_reviewed_at: string;
    next_review_at: string | null;
}

export interface GroupProgressSummary {
    status: GroupStatusValue;
    stage: number;
    next_review_at: string | null;
    cooldown_remaining_seconds: number | null;
}

export interface LevelProgressSummaryItem {
    total_stages: number;
    max_stages: number;
    mastery_percentage: number;
}

export interface LevelStatusCounts {
    all: number;
    ready: number;
    cooldown: number;
    not_started: number;
    completed: number;
}

export interface LevelGroupsProgressData {
    groups: Record<number, GroupProgressSummary>;
    counts: LevelStatusCounts;
}

const DB_NAME = 'vomorize';
const DB_VERSION = 1;
const STORE_NAME = 'learning_progress';

const STAGE_COOLDOWN_HOURS: Record<number, number | null> = {
    1: 12,
    2: 24,
    3: 48,
    4: 96,
    5: 168,
    6: null,
};

let dbPromise: Promise<IDBDatabase | null> | null = null;

function openDatabase(): Promise<IDBDatabase | null> {
    if (typeof window === 'undefined' || !window.indexedDB) {
        return Promise.resolve(null);
    }

    if (dbPromise) {
        return dbPromise;
    }

    dbPromise = new Promise((resolve) => {
        try {
            const request = window.indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    const store = db.createObjectStore(STORE_NAME, {
                        keyPath: 'group_id',
                    });
                    store.createIndex('level_id', 'level_id', {
                        unique: false,
                    });
                    store.createIndex('stage', 'stage', { unique: false });
                }
            };

            request.onsuccess = () => {
                resolve(request.result);
            };

            request.onerror = () => {
                dbPromise = null;
                resolve(null);
            };
        } catch {
            dbPromise = null;
            resolve(null);
        }
    });

    return dbPromise;
}

/**
 * Determine group learning status from a local progress record.
 * Mirrors backend App\Actions\DetermineGroupStatus.
 */
export function determineLocalGroupStatus(
    record: LocalLearningProgress | null | undefined,
    now: Date = new Date(),
): GroupProgressSummary {
    const stage = record?.stage ?? 0;

    if (stage >= 6) {
        return {
            status: 'completed',
            stage,
            next_review_at: record?.next_review_at ?? null,
            cooldown_remaining_seconds: null,
        };
    }

    if (stage > 0) {
        if (record?.next_review_at) {
            const nextReviewTime = new Date(record.next_review_at).getTime();
            const nowTime = now.getTime();

            if (!Number.isNaN(nextReviewTime) && nextReviewTime > nowTime) {
                return {
                    status: 'cooldown',
                    stage,
                    next_review_at: record.next_review_at,
                    cooldown_remaining_seconds: Math.max(
                        0,
                        Math.floor((nextReviewTime - nowTime) / 1000),
                    ),
                };
            }
        }

        return {
            status: 'ready',
            stage,
            next_review_at: record?.next_review_at ?? null,
            cooldown_remaining_seconds: null,
        };
    }

    return {
        status: 'not_started',
        stage: 0,
        next_review_at: record?.next_review_at ?? null,
        cooldown_remaining_seconds: null,
    };
}

/**
 * Compute per-level mastery percentages from local progress records.
 * Mirrors backend App\Actions\GetLevelsProgress.
 */
export function computeLevelsProgressMap(
    records: LocalLearningProgress[],
    levels: { id: number; groups_count: number }[],
): Record<number, LevelProgressSummaryItem> {
    const stagesByLevel = new Map<number, number>();

    for (const record of records) {
        const current = stagesByLevel.get(record.level_id) ?? 0;
        stagesByLevel.set(
            record.level_id,
            current + Math.min(6, Math.max(0, record.stage)),
        );
    }

    const result: Record<number, LevelProgressSummaryItem> = {};

    for (const level of levels) {
        const totalStages = stagesByLevel.get(level.id) ?? 0;
        const maxStages = (level.groups_count ?? 100) * 6;
        const masteryPercentage =
            maxStages > 0
                ? Math.min(
                      100,
                      Math.round((totalStages / maxStages) * 10000) / 100,
                  )
                : 0;

        result[level.id] = {
            total_stages: totalStages,
            max_stages: maxStages,
            mastery_percentage: masteryPercentage,
        };
    }

    return result;
}

/**
 * Compute group statuses and filter counts for a single level.
 * Mirrors backend App\Actions\GetLevelProgress.
 */
export function computeLevelProgressData(
    records: LocalLearningProgress[],
    groupsCount: number,
    now: Date = new Date(),
): LevelGroupsProgressData {
    const groups: Record<number, GroupProgressSummary> = {};
    let ready = 0;
    let cooldown = 0;
    let completed = 0;
    let notStarted = 0;

    for (const record of records) {
        const summary = determineLocalGroupStatus(record, now);
        groups[record.group_id] = summary;

        switch (summary.status) {
            case 'ready':
                ready++;
                break;
            case 'cooldown':
                cooldown++;
                break;
            case 'completed':
                completed++;
                break;
            case 'not_started':
                notStarted++;
                break;
        }
    }

    const unrecordedGroups = Math.max(0, groupsCount - records.length);
    notStarted += unrecordedGroups;

    return {
        groups,
        counts: {
            all: groupsCount,
            ready,
            cooldown,
            not_started: notStarted,
            completed,
        },
    };
}

/**
 * Calculate the updated SRS progress record after completing a quiz.
 * - Stage 1~5 pass threshold: 80; Stage 6 pass threshold: 90.
 * - Passed: advances stage by 1 (up to 6) with exact integer hour cooldown (12h/24h/48h/96h/168h/null).
 * - Failed: retains current stage and sets next_review_at to null (zero cooldown, immediate retry).
 */
export function calculateNextQuizProgress(
    currentRecord: LocalLearningProgress | null | undefined,
    groupId: number,
    levelId: number,
    score: number,
    now: Date = new Date(),
): LocalLearningProgress {
    const currentStage = currentRecord?.stage ?? 0;
    const targetStage = Math.min(6, currentStage + 1);
    const passThreshold = targetStage >= 6 ? 90 : 80;
    const isPassed = score >= passThreshold;

    const newStage = isPassed ? targetStage : currentStage;
    const cooldownHours = isPassed
        ? (STAGE_COOLDOWN_HOURS[newStage] ?? null)
        : null;

    const nextReviewAt =
        cooldownHours !== null
            ? new Date(now.getTime() + cooldownHours * 3_600_000).toISOString()
            : null;

    return {
        group_id: groupId,
        level_id: levelId,
        stage: newStage,
        last_score: score,
        last_reviewed_at: now.toISOString(),
        next_review_at: nextReviewAt,
    };
}

export const learningProgressDb = {
    async getGroupProgress(
        groupId: number,
    ): Promise<LocalLearningProgress | null> {
        const db = await openDatabase();
        if (!db) {
            return null;
        }

        return new Promise((resolve) => {
            try {
                const transaction = db.transaction(STORE_NAME, 'readonly');
                const store = transaction.objectStore(STORE_NAME);
                const request = store.get(groupId);

                request.onsuccess = () => {
                    resolve(
                        (request.result as LocalLearningProgress | undefined) ??
                            null,
                    );
                };
                request.onerror = () => resolve(null);
            } catch {
                resolve(null);
            }
        });
    },

    async getLevelProgressList(
        levelId: number,
    ): Promise<LocalLearningProgress[]> {
        const db = await openDatabase();
        if (!db) {
            return [];
        }

        return new Promise((resolve) => {
            try {
                const transaction = db.transaction(STORE_NAME, 'readonly');
                const store = transaction.objectStore(STORE_NAME);
                const index = store.index('level_id');
                const request = index.getAll(levelId);

                request.onsuccess = () => {
                    resolve(
                        (request.result as
                            | LocalLearningProgress[]
                            | undefined) ?? [],
                    );
                };
                request.onerror = () => resolve([]);
            } catch {
                resolve([]);
            }
        });
    },

    async getAllProgress(): Promise<LocalLearningProgress[]> {
        const db = await openDatabase();
        if (!db) {
            return [];
        }

        return new Promise((resolve) => {
            try {
                const transaction = db.transaction(STORE_NAME, 'readonly');
                const store = transaction.objectStore(STORE_NAME);
                const request = store.getAll();

                request.onsuccess = () => {
                    resolve(
                        (request.result as
                            | LocalLearningProgress[]
                            | undefined) ?? [],
                    );
                };
                request.onerror = () => resolve([]);
            } catch {
                resolve([]);
            }
        });
    },

    async saveGroupProgress(record: LocalLearningProgress): Promise<void> {
        const db = await openDatabase();
        if (!db) {
            return;
        }

        return new Promise((resolve) => {
            try {
                const transaction = db.transaction(STORE_NAME, 'readwrite');
                const store = transaction.objectStore(STORE_NAME);
                const request = store.put(record);

                request.onsuccess = () => resolve();
                request.onerror = () => resolve();
            } catch {
                resolve();
            }
        });
    },

    async recordQuizResult(
        groupId: number,
        levelId: number,
        score: number,
        now: Date = new Date(),
    ): Promise<LocalLearningProgress> {
        const existing = await this.getGroupProgress(groupId);
        const updated = calculateNextQuizProgress(
            existing,
            groupId,
            levelId,
            score,
            now,
        );
        await this.saveGroupProgress(updated);

        return updated;
    },

    async clearAllProgress(): Promise<void> {
        const db = await openDatabase();
        if (!db) {
            return;
        }

        return new Promise((resolve) => {
            try {
                const transaction = db.transaction(STORE_NAME, 'readwrite');
                const store = transaction.objectStore(STORE_NAME);
                const request = store.clear();

                request.onsuccess = () => resolve();
                request.onerror = () => resolve();
            } catch {
                resolve();
            }
        });
    },
};
