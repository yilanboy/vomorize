import { describe, expect, it } from 'vite-plus/test';
import {
    calculateNextQuizProgress,
    computeLevelProgressData,
    computeLevelsProgressMap,
    determineLocalGroupStatus,
    type LocalLearningProgress,
} from '@/lib/learningProgressDb';

const NOW = new Date('2026-11-19T12:00:00.000Z');

function makeRecord(
    overrides: Partial<LocalLearningProgress> = {},
): LocalLearningProgress {
    return {
        group_id: 1,
        level_id: 1,
        stage: 1,
        last_score: 90,
        last_reviewed_at: '2026-11-18T12:00:00.000Z',
        next_review_at: null,
        ...overrides,
    };
}

describe('determineLocalGroupStatus', () => {
    it('returns not_started when record is null or undefined', () => {
        expect(determineLocalGroupStatus(null, NOW)).toEqual({
            status: 'not_started',
            stage: 0,
            next_review_at: null,
            cooldown_remaining_seconds: null,
        });

        expect(determineLocalGroupStatus(undefined, NOW)).toEqual({
            status: 'not_started',
            stage: 0,
            next_review_at: null,
            cooldown_remaining_seconds: null,
        });
    });

    it('returns not_started when stage is 0', () => {
        const record = makeRecord({ stage: 0, next_review_at: null });

        expect(determineLocalGroupStatus(record, NOW)).toEqual({
            status: 'not_started',
            stage: 0,
            next_review_at: null,
            cooldown_remaining_seconds: null,
        });
    });

    it('returns completed when stage is 6 or greater', () => {
        const record = makeRecord({
            stage: 6,
            next_review_at: '2026-11-20T12:00:00.000Z',
        });

        expect(determineLocalGroupStatus(record, NOW)).toEqual({
            status: 'completed',
            stage: 6,
            next_review_at: '2026-11-20T12:00:00.000Z',
            cooldown_remaining_seconds: null,
        });
    });

    it('returns cooldown with remaining seconds when next_review_at is in the future', () => {
        const record = makeRecord({
            stage: 2,
            next_review_at: '2026-11-19T12:01:30.900Z',
        });

        expect(determineLocalGroupStatus(record, NOW)).toEqual({
            status: 'cooldown',
            stage: 2,
            next_review_at: '2026-11-19T12:01:30.900Z',
            cooldown_remaining_seconds: 90,
        });
    });

    it('returns ready at exact cooldown expiry and when next_review_at is in the past', () => {
        const exactExpiry = makeRecord({
            stage: 1,
            next_review_at: NOW.toISOString(),
        });
        const expired = makeRecord({
            stage: 3,
            next_review_at: '2026-11-19T11:59:59.000Z',
        });

        expect(determineLocalGroupStatus(exactExpiry, NOW)).toEqual({
            status: 'ready',
            stage: 1,
            next_review_at: NOW.toISOString(),
            cooldown_remaining_seconds: null,
        });

        expect(determineLocalGroupStatus(expired, NOW)).toEqual({
            status: 'ready',
            stage: 3,
            next_review_at: '2026-11-19T11:59:59.000Z',
            cooldown_remaining_seconds: null,
        });
    });

    it('returns ready when stage is 1..5 and next_review_at is null or invalid', () => {
        const noCooldown = makeRecord({ stage: 4, next_review_at: null });
        const invalidDate = makeRecord({
            stage: 4,
            next_review_at: 'not-a-date',
        });

        expect(determineLocalGroupStatus(noCooldown, NOW).status).toBe('ready');
        expect(determineLocalGroupStatus(invalidDate, NOW).status).toBe(
            'ready',
        );
    });
});

describe('calculateNextQuizProgress', () => {
    it('advances from stage 0 to stage 1 with 12h cooldown at exact threshold 80', () => {
        const result = calculateNextQuizProgress(null, 10, 2, 80, NOW);

        expect(result).toEqual({
            group_id: 10,
            level_id: 2,
            stage: 1,
            last_score: 80,
            last_reviewed_at: NOW.toISOString(),
            next_review_at: '2026-11-20T00:00:00.000Z',
        });
    });

    it('retains stage 0 with null cooldown when failing stage 1 at 79', () => {
        const result = calculateNextQuizProgress(null, 10, 2, 79, NOW);

        expect(result).toEqual({
            group_id: 10,
            level_id: 2,
            stage: 0,
            last_score: 79,
            last_reviewed_at: NOW.toISOString(),
            next_review_at: null,
        });
    });

    it.each([
        { fromStage: 1, toStage: 2, cooldownHours: 24 },
        { fromStage: 2, toStage: 3, cooldownHours: 48 },
        { fromStage: 3, toStage: 4, cooldownHours: 96 },
        { fromStage: 4, toStage: 5, cooldownHours: 168 },
    ])(
        'advances from stage $fromStage to stage $toStage with $cooldownHours h cooldown at score 80',
        ({ fromStage, toStage, cooldownHours }) => {
            const current = makeRecord({ stage: fromStage });
            const result = calculateNextQuizProgress(current, 1, 1, 80, NOW);

            const expectedNextReview = new Date(
                NOW.getTime() + cooldownHours * 3_600_000,
            ).toISOString();

            expect(result.stage).toBe(toStage);
            expect(result.last_score).toBe(80);
            expect(result.next_review_at).toBe(expectedNextReview);
        },
    );

    it('retains current stage with zero cooldown when failing stages 2 through 5', () => {
        const current = makeRecord({
            stage: 3,
            next_review_at: '2026-11-19T10:00:00.000Z',
        });

        const result = calculateNextQuizProgress(current, 1, 1, 79, NOW);

        expect(result).toEqual({
            group_id: 1,
            level_id: 1,
            stage: 3,
            last_score: 79,
            last_reviewed_at: NOW.toISOString(),
            next_review_at: null,
        });
    });

    it('requires 90 points to advance from stage 5 to stage 6', () => {
        const current = makeRecord({ stage: 5, last_score: 90 });

        const failed = calculateNextQuizProgress(current, 1, 1, 89, NOW);
        expect(failed).toEqual({
            group_id: 1,
            level_id: 1,
            stage: 5,
            last_score: 89,
            last_reviewed_at: NOW.toISOString(),
            next_review_at: null,
        });

        const passed = calculateNextQuizProgress(current, 1, 1, 90, NOW);
        expect(passed).toEqual({
            group_id: 1,
            level_id: 1,
            stage: 6,
            last_score: 90,
            last_reviewed_at: NOW.toISOString(),
            next_review_at: null,
        });
    });

    it('retains stage 6 permanently and preserves the higher score on self-test retakes', () => {
        const mastered = makeRecord({ stage: 6, last_score: 95 });

        const lowerScore = calculateNextQuizProgress(mastered, 1, 1, 70, NOW);
        expect(lowerScore).toEqual({
            group_id: 1,
            level_id: 1,
            stage: 6,
            last_score: 95,
            last_reviewed_at: NOW.toISOString(),
            next_review_at: null,
        });

        const higherScore = calculateNextQuizProgress(mastered, 1, 1, 100, NOW);
        expect(higherScore).toEqual({
            group_id: 1,
            level_id: 1,
            stage: 6,
            last_score: 100,
            last_reviewed_at: NOW.toISOString(),
            next_review_at: null,
        });
    });
});

describe('computeLevelsProgressMap', () => {
    it('computes total stages, max stages, and rounded mastery percentage per level', () => {
        const records = [
            makeRecord({ group_id: 1, level_id: 1, stage: 1 }),
            makeRecord({ group_id: 2, level_id: 1, stage: 6 }),
        ];

        const map = computeLevelsProgressMap(records, [
            { id: 1, groups_count: 100 },
            { id: 2, groups_count: 50 },
        ]);

        expect(map).toEqual({
            1: {
                total_stages: 7,
                max_stages: 600,
                mastery_percentage: 1.17,
            },
            2: {
                total_stages: 0,
                max_stages: 300,
                mastery_percentage: 0,
            },
        });
    });

    it('clamps out-of-range stages and handles zero-group levels safely', () => {
        const records = [
            makeRecord({ group_id: 1, level_id: 1, stage: 99 }),
            makeRecord({ group_id: 2, level_id: 1, stage: -3 }),
        ];

        const map = computeLevelsProgressMap(records, [
            { id: 1, groups_count: 1 },
            { id: 2, groups_count: 0 },
        ]);

        expect(map[1]).toEqual({
            total_stages: 6,
            max_stages: 6,
            mastery_percentage: 100,
        });
        expect(map[2]).toEqual({
            total_stages: 0,
            max_stages: 0,
            mastery_percentage: 0,
        });
    });
});

describe('computeLevelProgressData', () => {
    it('categorizes group statuses and counts unrecorded groups as not_started', () => {
        const records = [
            makeRecord({
                group_id: 1,
                stage: 1,
                next_review_at: '2026-11-19T18:00:00.000Z',
            }),
            makeRecord({
                group_id: 2,
                stage: 2,
                next_review_at: '2026-11-19T11:00:00.000Z',
            }),
            makeRecord({ group_id: 3, stage: 6, next_review_at: null }),
            makeRecord({ group_id: 4, stage: 0, next_review_at: null }),
        ];

        const result = computeLevelProgressData(records, 10, NOW);

        expect(result.groups[1]?.status).toBe('cooldown');
        expect(result.groups[2]?.status).toBe('ready');
        expect(result.groups[3]?.status).toBe('completed');
        expect(result.groups[4]?.status).toBe('not_started');
        expect(result.counts).toEqual({
            all: 10,
            ready: 1,
            cooldown: 1,
            completed: 1,
            not_started: 7,
        });
    });

    it('never produces negative not_started when records exceed groupsCount', () => {
        const records = [
            makeRecord({ group_id: 1, stage: 6 }),
            makeRecord({ group_id: 2, stage: 6 }),
        ];

        const result = computeLevelProgressData(records, 1, NOW);

        expect(result.counts).toEqual({
            all: 1,
            ready: 0,
            cooldown: 0,
            completed: 2,
            not_started: 0,
        });
    });
});
