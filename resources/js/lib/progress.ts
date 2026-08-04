export interface GuestGroupProgress {
    group_id: number;
    stage: number;
    last_score: number | null;
    last_reviewed_at: string | null; // ISO string
    next_review_at: string | null; // ISO string
}

export const INTERVALS_MS: Record<number, number> = {
    1: 12 * 3600 * 1000,
    2: 24 * 3600 * 1000,
    3: 2 * 24 * 3600 * 1000,
    4: 4 * 24 * 3600 * 1000,
    5: 7 * 24 * 3600 * 1000,
    6: 15 * 24 * 3600 * 1000,
};

export const STAGE_0_FAIL_PENALTY_MS = 12 * 3600 * 1000;
export const LATER_FAIL_PENALTY_MS = 24 * 3600 * 1000;

export function calculateNextState(
    currentStage: number,
    score: number,
    nowMs: number = Date.now(),
): {
    stage: number;
    last_score: number;
    last_reviewed_at: string;
    next_review_at: string | null;
    passed: boolean;
} {
    const passed = score >= 90;
    const nowIso = new Date(nowMs).toISOString();

    if (passed) {
        const newStage = Math.min(6, currentStage + 1);
        const nextReviewAtMs =
            newStage === 6 ? null : nowMs + (INTERVALS_MS[newStage] ?? INTERVALS_MS[1]);

        return {
            stage: newStage,
            last_score: score,
            last_reviewed_at: nowIso,
            next_review_at: nextReviewAtMs ? new Date(nextReviewAtMs).toISOString() : null,
            passed: true,
        };
    } else {
        const newStage = currentStage;
        let penaltyMs: number | null = null;

        if (currentStage === 6) {
            penaltyMs = null;
        } else if (currentStage === 0) {
            penaltyMs = STAGE_0_FAIL_PENALTY_MS;
        } else {
            penaltyMs = LATER_FAIL_PENALTY_MS;
        }

        const nextReviewAtMs = penaltyMs !== null ? nowMs + penaltyMs : null;

        return {
            stage: newStage,
            last_score: score,
            last_reviewed_at: nowIso,
            next_review_at: nextReviewAtMs ? new Date(nextReviewAtMs).toISOString() : null,
            passed: false,
        };
    }
}

export function deriveGroupStatus(
    stage: number,
    lastScore: number | null,
    nextReviewAtIso: string | null,
    nowMs: number = Date.now(),
): 'not_started' | 'locked' | 'penalty' | 'ready' | 'completed' {
    if (stage === 6) {
        return 'completed';
    }

    const isLocked = nextReviewAtIso !== null && new Date(nextReviewAtIso).getTime() > nowMs;

    if (isLocked) {
        if (lastScore !== null && lastScore < 90) {
            return 'penalty';
        }

        return 'locked';
    }

    if (stage === 0 && lastScore === null) {
        return 'not_started';
    }

    return 'ready';
}

/**
 * How far a group has travelled through its current wait, as a percentage.
 *
 * Both ends are required: without the last review there is no start point, and guessing one
 * from the stage interval table would make this a second source of truth for wait durations.
 * Returns 0 when either end is missing, so a tile with no recorded review draws no wash.
 */
export function calculateFillPercent(
    lastReviewedAtIso: string | null,
    nextReviewAtIso: string | null,
    nowMs: number = Date.now(),
): number {
    if (lastReviewedAtIso === null || nextReviewAtIso === null) {
        return 0;
    }

    const startMs = new Date(lastReviewedAtIso).getTime();
    const endMs = new Date(nextReviewAtIso).getTime();

    if (Number.isNaN(startMs) || Number.isNaN(endMs) || endMs <= startMs) {
        return 0;
    }

    const elapsed = ((nowMs - startMs) / (endMs - startMs)) * 100;

    return Math.min(100, Math.max(0, elapsed));
}

const STORAGE_KEY = 'vomorize_guest_progress';

export function getGuestProgressMap(): Record<number, GuestGroupProgress> {
    if (typeof localStorage === 'undefined') {
        return {};
    }

    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        return raw ? JSON.parse(raw) : {};
    } catch {
        return {};
    }
}

export function getGuestGroupProgress(groupId: number): GuestGroupProgress | null {
    const map = getGuestProgressMap();

    return map[groupId] ?? null;
}

export function saveGuestGroupProgress(groupId: number, progress: GuestGroupProgress): void {
    if (typeof localStorage === 'undefined') {
        return;
    }

    const map = getGuestProgressMap();
    map[groupId] = progress;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(map));
}

export function clearGuestProgress(): void {
    if (typeof localStorage === 'undefined') {
        return;
    }

    localStorage.removeItem(STORAGE_KEY);
}
