<script lang="ts">
    import { page, Link } from '@inertiajs/svelte';
    import Check from '@lucide/svelte/icons/check';
    import { currentLocale, localized, translations } from '@/lib/locale.svelte';
    import { getGuestProgressMap, deriveGroupStatus, calculateFillPercent } from '@/lib/progress';

    /**
     * The five statuses collapse into three tiers, so that visual weight tracks what a
     * learner can act on rather than how far through the schedule a group happens to be.
     */
    type Tier = 'actionable' | 'pending' | 'completed';

    interface GroupItem {
        id: number;
        sequence: number;
        level_id: number;
        stage: number;
        last_score: number | null;
        last_reviewed_at: string | null;
        next_review_at: string | null;
        status: 'not_started' | 'locked' | 'penalty' | 'ready' | 'completed';
    }

    interface LevelTranslationData {
        locale: string;
        name: string;
        description: string;
    }

    interface LevelData {
        id: number;
        translations: Record<string, LevelTranslationData>;
    }

    let { level, groups = [] } = $props<{
        level: LevelData;
        groups: GroupItem[];
    }>();

    let t = $derived(translations());
    let isGuest = $derived(!page.props.auth?.user);

    /**
     * A guest's record never reaches the server, so the statuses it sent for them are inferred
     * from its own blank slate. Marking the render says so, and lets the pre-paint script in the
     * document head hold back the tiles it can see local storage disagrees about — see the
     * reasoning there. The mark is server-render-only: by the time the client renders, local
     * storage is in hand and the tiles below are drawn from it.
     */
    let statusesAreInferred = $derived(isGuest && typeof window === 'undefined');

    /**
     * A group's status is a function of the current moment, so a page left open would
     * otherwise never notice one coming due — the tile would sit in the pending tier until
     * the learner reloaded. Re-read on a one-minute cadence.
     */
    let nowMs = $state(Date.now());

    $effect(() => {
        const timer = setInterval(() => {
            nowMs = Date.now();
        }, 60_000);

        return () => clearInterval(timer);
    });

    /**
     * Guest progress, when present, wins over the server's record; otherwise the server's
     * status stands and only its one time-dependent edge is advanced.
     */
    let displayGroups = $derived.by<GroupItem[]>(() => {
        const guestMap = isGuest && typeof window !== 'undefined' ? getGuestProgressMap() : {};

        return groups.map((g: GroupItem) => {
            const p = guestMap[g.id];

            if (!p) {
                return { ...g, status: promoteIfDue(g.status, g.next_review_at, nowMs) };
            }

            return {
                ...g,
                stage: p.stage,
                last_score: p.last_score,
                last_reviewed_at: p.last_reviewed_at,
                next_review_at: p.next_review_at,
                status: deriveGroupStatus(p.stage, p.last_score, p.next_review_at, nowMs),
            };
        });
    });

    let completedCount = $derived(displayGroups.filter((g) => g.status === 'completed').length);
    let actionableCount = $derived(
        displayGroups.filter((g) => tierOf(g.status) === 'actionable').length,
    );

    let levelTranslation = $derived<Omit<LevelTranslationData, 'locale'>>(
        localized<LevelTranslationData>(level.translations) ?? { name: '', description: '' },
    );

    /**
     * The only thing the passing of time can change about a status the server already derived
     * is that a wait has run out, and only in that one direction. Advancing exactly that much
     * leaves the server's derivation authoritative: re-deriving wholesale on the client would
     * also re-decide locked versus penalty, which the two implementations disagree about (the
     * client treats anything under the 90 pass mark as a penalty, the server only under 60) —
     * a distinction this change has no business moving.
     */
    function promoteIfDue(
        status: GroupItem['status'],
        nextReviewAtIso: string | null,
        atMs: number,
    ): GroupItem['status'] {
        if (status !== 'locked' && status !== 'penalty') {
            return status;
        }

        if (nextReviewAtIso !== null && new Date(nextReviewAtIso).getTime() > atMs) {
            return status;
        }

        return 'ready';
    }

    /**
     * `not_started` is actionable, not pending: the group page already offers an enabled
     * start action for it, so grouping it with the waiting statuses had the grid
     * contradicting the destination it links to.
     */
    function tierOf(status: GroupItem['status']): Tier {
        if (status === 'completed') {
            return 'completed';
        }

        if (status === 'ready' || status === 'not_started') {
            return 'actionable';
        }

        return 'pending';
    }

    function getStatusLabel(status: GroupItem['status']): string {
        if (status === 'completed') {
            return t['completed'];
        }

        if (status === 'ready') {
            return t['ready'];
        }

        if (status === 'penalty') {
            return t['cooldown'];
        }

        if (status === 'locked') {
            return t['locked'];
        }

        return t['not_started'];
    }

    function getGroupClasses(status: GroupItem['status'], tier: Tier): string {
        if (tier === 'completed') {
            return 'border border-emerald-500/25 bg-emerald-500/6 text-emerald-600 shadow-2xs hover:bg-emerald-500/10 dark:bg-emerald-900/50 dark:text-emerald-400';
        }

        if (tier === 'actionable') {
            if (status === 'ready') {
                return 'border border-orange-500/30 bg-orange-500/7 text-zinc-900 shadow-2xs hover:border-orange-500/50 hover:bg-orange-500/12 dark:border-orange-400/50 dark:bg-orange-400/15 dark:text-zinc-50 dark:hover:border-orange-400/70 dark:hover:bg-orange-400/25';
            }

            // not_started
            return 'bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 hover:border-zinc-400 dark:hover:border-zinc-600 text-zinc-900 dark:text-zinc-50 shadow-sm';
        }

        if (tier === 'pending') {
            if (status === 'penalty') {
                return 'bg-zinc-100 border border-zinc-200 text-zinc-400 dark:bg-zinc-950 dark:border-zinc-800 dark:text-zinc-500';
            }

            // locked
            return 'border border-zinc-400 text-zinc-600 hover:border-zinc-500 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-400';
        }

        return '';
    }
</script>

<main class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <div class="flex items-center space-x-3">
                <Link
                    href={`/${currentLocale()}`}
                    class="text-sm font-semibold text-zinc-900 underline-offset-4 hover:underline dark:text-zinc-50"
                >
                    &larr; {t['home']}
                </Link>
                <span class="text-sm text-zinc-500/50 dark:text-zinc-400/50">/</span>
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400"
                    >{levelTranslation.name}</span
                >
            </div>

            <h1
                data-test="level-name"
                class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-50"
            >
                {levelTranslation.name}
            </h1>
            <p data-test="level-description" class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {levelTranslation.description}
            </p>
        </div>

        <div
            class="inline-flex items-center space-x-2 rounded-full border border-zinc-200 bg-zinc-50 px-3.5 py-1.5 text-sm font-medium text-zinc-900 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-50"
        >
            <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
            <span class={statusesAreInferred ? 'summary-unverified' : ''}
                >{completedCount}/{groups.length}
                {t['completed']} · {actionableCount}
                {t['actionable']}</span
            >
        </div>
    </div>

    <div class="grid grid-cols-4 gap-3 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10">
        {#each displayGroups as g, index}
            {@const tier = tierOf(g.status)}
            {@const fill =
                tier === 'pending'
                    ? calculateFillPercent(g.last_reviewed_at, g.next_review_at, nowMs)
                    : 0}
            <Link
                href={`/${currentLocale()}/groups/${g.id}`}
                data-test="group-tile"
                data-group-id={g.id}
                aria-label={`${t['group']} ${g.sequence}: ${getStatusLabel(g.status)}`}
                style={fill > 0
                    ? `--tile-fill: ${fill}%; --tile-wash-delay: ${index * 12}ms`
                    : undefined}
                class="group relative flex aspect-square items-center justify-center rounded-md text-2xl font-semibold tracking-tight transition focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-100 dark:focus-visible:ring-zinc-600 dark:focus-visible:ring-offset-zinc-950
                    {statusesAreInferred ? 'tile-unverified' : ''}
                    {fill > 0 ? 'tile-wash' : ''}
                    {getGroupClasses(g.status, tier)}"
            >
                {#if tier === 'completed'}
                    <Check class="size-6" aria-hidden="true" />
                {:else}
                    <span>{g.sequence}</span>
                {/if}
                <span class="sr-only">{getStatusLabel(g.status)}</span>
            </Link>
        {/each}
    </div>
</main>
