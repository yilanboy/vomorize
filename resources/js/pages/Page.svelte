<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Brain from '@lucide/svelte/icons/brain';
    import Check from '@lucide/svelte/icons/check';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import CircleAlert from '@lucide/svelte/icons/circle-alert';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';
    import Smartphone from '@lucide/svelte/icons/smartphone';
    import Sparkles from '@lucide/svelte/icons/sparkles';
    import Target from '@lucide/svelte/icons/target';
    import TriangleAlert from '@lucide/svelte/icons/triangle-alert';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import Zap from '@lucide/svelte/icons/zap';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { t } from '@/lib/i18n';
    import { cn, toUrl } from '@/lib/utils';
    import { dashboard, login } from '@/routes';

    const auth = $derived(page.props.auth);

    interface LevelData {
        id: number;
        name: string;
        description: string;
        vocabularies_count?: number;
        groups_count?: number;
    }

    let {
        levels = [],
    }: {
        levels?: LevelData[];
    } = $props();

    // Audio pronunciation preview
    let playingAudioTarget = $state<'word' | 'sentence' | null>(null);
    function playDemoAudio(text: string, target: 'word' | 'sentence') {
        if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
            playingAudioTarget = target;
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'en-US';
            utterance.rate = 0.9;
            utterance.onend = () => {
                if (playingAudioTarget === target) {
                    playingAudioTarget = null;
                }
            };
            utterance.onerror = () => {
                if (playingAudioTarget === target) {
                    playingAudioTarget = null;
                }
            };
            window.speechSynthesis.speak(utterance);
        }
    }

    // Sample mastery percentages for levels preview on landing page
    const levelMasteryPercentages = [70, 55, 40, 25, 15, 10, 5];

    function parseLevelInfo(item: LevelData) {
        if (item.description?.includes('：')) {
            const [title, ...rest] = item.description.split('：');
            return {
                title: title.trim(),
                description: rest.join('：').trim(),
            };
        }
        return {
            title: item.name,
            description: item.description,
        };
    }

    const srsTimelineSteps = [
        {
            step: '1',
            timeKey: 'ui.welcome.srs.step_1_time',
            descKey: 'ui.welcome.srs.step_1_desc',
            isFinal: false,
        },
        {
            step: '2',
            timeKey: 'ui.welcome.srs.step_2_time',
            descKey: 'ui.welcome.srs.step_2_desc',
            isFinal: false,
        },
        {
            step: '3',
            timeKey: 'ui.welcome.srs.step_3_time',
            descKey: 'ui.welcome.srs.step_3_desc',
            isFinal: false,
        },
        {
            step: '4',
            timeKey: 'ui.welcome.srs.step_4_time',
            descKey: 'ui.welcome.srs.step_4_desc',
            isFinal: false,
        },
        {
            step: '✓',
            timeKey: 'ui.welcome.srs.step_5_time',
            descKey: 'ui.welcome.srs.step_5_desc',
            isFinal: true,
        },
    ];

    const features = [
        {
            icon: Brain,
            titleKey: 'ui.welcome.features.srs_title',
            descKey: 'ui.welcome.features.srs_desc',
            pillKey: 'ui.welcome.features.srs_pill',
            iconClass:
                'text-blue-600 bg-blue-50 dark:bg-blue-950/40 dark:text-blue-400',
            borderHoverClass:
                'hover:border-blue-300 dark:hover:border-blue-700',
            isQuiz: false,
        },
        {
            icon: Zap,
            titleKey: 'ui.welcome.features.guest_title',
            descKey: 'ui.welcome.features.guest_desc',
            pillKey: 'ui.welcome.features.guest_pill',
            iconClass:
                'text-blue-600 bg-blue-50 dark:bg-blue-950/40 dark:text-blue-400',
            borderHoverClass:
                'hover:border-blue-300 dark:hover:border-blue-700',
            isQuiz: false,
        },
        {
            icon: Target,
            titleKey: 'ui.welcome.features.quiz_title',
            descKey: 'ui.welcome.features.quiz_desc',
            pillKey: null,
            iconClass:
                'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400',
            borderHoverClass:
                'hover:border-emerald-300 dark:hover:border-emerald-700',
            isQuiz: true,
        },
        {
            icon: Smartphone,
            titleKey: 'ui.welcome.features.mobile_title',
            descKey: 'ui.welcome.features.mobile_desc',
            pillKey: 'ui.welcome.features.mobile_pill',
            iconClass:
                'text-slate-700 bg-slate-100 dark:bg-slate-800 dark:text-slate-300',
            borderHoverClass:
                'hover:border-slate-300 dark:hover:border-slate-700',
            isQuiz: false,
        },
    ];
</script>

<AppHead title={t('ui.welcome.hero.title')} />

<main
    class="space-y-20 py-8 selection:bg-blue-600 selection:text-white sm:space-y-28 sm:py-16"
>
    <!-- 1. Hero Section -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div
            class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12 lg:gap-12 xl:gap-16"
        >
            <!-- Left Hero Content -->
            <div class="flex flex-col items-start text-left lg:col-span-7">
                <!-- Pill Badge -->
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-blue-200/80 bg-blue-50/80 px-3.5 py-1.5 text-sm font-semibold tracking-wide text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300"
                >
                    <Sparkles class="size-4 text-blue-600 dark:text-blue-400" />
                    <span>{t('ui.welcome.hero.badge')}</span>
                </div>

                <!-- Main Headline -->
                <h1
                    class="text-foreground mt-6 text-3xl font-extrabold tracking-tight sm:text-4xl md:text-5xl lg:text-6xl lg:leading-[1.12]"
                >
                    {t('ui.welcome.hero.title')}
                </h1>

                <!-- Subheadline -->
                <p
                    class="text-muted-foreground mt-6 max-w-2xl text-base leading-relaxed sm:text-lg sm:leading-relaxed"
                >
                    {t('ui.welcome.hero.subtitle')}
                </p>

                <!-- CTA Button Group -->
                <div
                    class="mt-8 flex w-full flex-col items-stretch gap-3.5 sm:w-auto sm:flex-row sm:items-center"
                >
                    <Button
                        asChild
                        size="lg"
                        class={cn(
                            'w-full bg-blue-600 text-base font-semibold text-white shadow-md shadow-blue-500/20 transition-colors hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 sm:w-auto',
                        )}
                    >
                        {#snippet children(props)}
                            <Link href="/levels" class={props.class}>
                                <span
                                    >{t('ui.welcome.hero.start_learning')}</span
                                >
                                <ArrowRight class="size-4.5" />
                            </Link>
                        {/snippet}
                    </Button>

                    <Button
                        asChild
                        variant="outline"
                        size="lg"
                        class={cn('w-full text-base sm:w-auto')}
                    >
                        {#snippet children(props)}
                            <a href="#levels" class={props.class}>
                                <span
                                    >{t('ui.welcome.hero.explore_levels')}</span
                                >
                            </a>
                        {/snippet}
                    </Button>

                    {#if auth.user}
                        <Button
                            asChild
                            variant="ghost"
                            size="lg"
                            class={cn('w-full text-base sm:w-auto')}
                        >
                            {#snippet children(props)}
                                <Link
                                    href={toUrl(dashboard())}
                                    class={props.class}
                                >
                                    {t('ui.welcome.hero.dashboard')}
                                </Link>
                            {/snippet}
                        </Button>
                    {:else}
                        <Button
                            asChild
                            variant="ghost"
                            size="lg"
                            class={cn('w-full text-base sm:w-auto')}
                        >
                            {#snippet children(props)}
                                <Link href={toUrl(login())} class={props.class}>
                                    {t('ui.welcome.hero.login')}
                                </Link>
                            {/snippet}
                        </Button>
                    {/if}
                </div>

                <!-- Trust Line -->
                <div
                    class="text-muted-foreground mt-8 flex flex-wrap items-center gap-2 text-sm font-medium"
                >
                    <span
                        class="flex items-center gap-1.5 font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        <CheckCircle2 class="size-4.5" />
                        <span>{t('ui.welcome.hero.free_notice')}</span>
                    </span>
                </div>
            </div>

            <!-- Right Hero Product Visual / Vocabulary Card -->
            <div class="relative flex justify-center lg:col-span-5">
                <section
                    class="border-border/80 bg-card w-full max-w-md rounded-2xl border p-5 shadow-lg shadow-slate-900/5 transition-all sm:p-6 dark:shadow-black/30"
                    aria-label={t('ui.welcome.hero.demo_word')}
                >
                    <!-- Card Header: Word, POS, Pronunciation, Word Audio Button -->
                    <div class="flex items-start justify-between gap-4">
                        <div
                            class="flex flex-wrap items-baseline gap-2.5 sm:gap-3"
                        >
                            <!-- Target Word -->
                            <h2
                                class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
                            >
                                {t('ui.welcome.hero.demo_word')}
                            </h2>

                            <!-- Part of Speech Pill -->
                            <span
                                class="bg-muted text-foreground/80 rounded-md px-2.5 py-0.5 text-sm font-semibold"
                            >
                                {t('ui.welcome.hero.demo_pos')}
                            </span>

                            <!-- IPA Pronunciation -->
                            <span
                                class="text-muted-foreground font-mono text-sm sm:text-base"
                            >
                                {t('ui.welcome.hero.demo_phonetic')}
                            </span>
                        </div>

                        <!-- 40x40px Word Audio Button -->
                        <button
                            type="button"
                            onclick={() =>
                                playDemoAudio(
                                    t('ui.welcome.hero.demo_word'),
                                    'word',
                                )}
                            class={{
                                'flex size-10 shrink-0 items-center justify-center rounded-full border transition-all duration-150 active:scale-95': true,
                                'animate-pulse border-blue-600 bg-blue-600 text-white shadow-xs ring-4 ring-blue-500/20':
                                    playingAudioTarget === 'word',
                                'border-border/70 bg-muted/40 text-muted-foreground dark:bg-muted/30 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600 dark:hover:border-blue-800/60 dark:hover:bg-blue-950/60 dark:hover:text-blue-300':
                                    playingAudioTarget !== 'word',
                            }}
                            title={t('ui.intro.card.play_word')}
                            aria-label={t('ui.intro.card.play_word')}
                        >
                            <Volume2 class="size-4.5" />
                        </button>
                    </div>

                    <!-- Definition -->
                    <p
                        class="text-foreground mt-2.5 text-base font-medium sm:text-lg"
                    >
                        {t('ui.welcome.hero.demo_translation')}
                    </p>

                    <!-- Integrated Example Sentence Section -->
                    <div class="border-border/60 mt-4 border-t pt-3.5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <p
                                    class="text-foreground text-sm leading-relaxed font-medium sm:text-base"
                                >
                                    {t('ui.welcome.hero.demo_example')}
                                </p>
                                <p
                                    class="text-muted-foreground text-sm leading-normal"
                                >
                                    {t(
                                        'ui.welcome.hero.demo_example_translation',
                                    )}
                                </p>
                            </div>

                            <!-- 40x40px Sentence Audio Button -->
                            <button
                                type="button"
                                onclick={() =>
                                    playDemoAudio(
                                        t('ui.welcome.hero.demo_example'),
                                        'sentence',
                                    )}
                                class={{
                                    'flex size-10 shrink-0 items-center justify-center rounded-full border transition-all duration-150 active:scale-95': true,
                                    'animate-pulse border-blue-600 bg-blue-600 text-white shadow-xs ring-4 ring-blue-500/20':
                                        playingAudioTarget === 'sentence',
                                    'border-border/70 bg-muted/40 text-muted-foreground dark:bg-muted/30 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600 dark:hover:border-blue-800/60 dark:hover:bg-blue-950/60 dark:hover:text-blue-300':
                                        playingAudioTarget !== 'sentence',
                                }}
                                title={t('ui.intro.card.play_sentence')}
                                aria-label={t('ui.intro.card.play_sentence')}
                            >
                                <Volume2 class="size-4.5" />
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </section>

    <!-- 2. SRS Cognitive Science / Ebbinghaus Comparison Section -->
    <section
        class="border-border/80 bg-muted/30 border-y py-16 sm:py-24"
        id="srs-science"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Section Header (Clean title without banned kickers/eyebrows) -->
            <div class="mx-auto mb-14 max-w-3xl text-center sm:mb-16">
                <h2
                    class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl"
                >
                    {t('ui.welcome.srs.title')}
                </h2>
                <p
                    class="text-muted-foreground mt-4 text-base leading-relaxed sm:text-lg"
                >
                    {t('ui.welcome.srs.subtitle')}
                </p>
            </div>

            <!-- Side-by-Side Comparison Cards -->
            <div class="mb-12 grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Left Card: 傳統死記硬背 (Alert Red Theme) -->
                <div
                    class="bg-card border-border/90 relative overflow-hidden rounded-2xl border p-6 shadow-xs sm:p-8"
                >
                    <div
                        class="mb-5 flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex size-9 items-center justify-center rounded-xl bg-red-100 font-bold text-red-600 dark:bg-red-950/70 dark:text-red-400"
                            >
                                <TriangleAlert class="size-5" />
                            </div>
                            <h3 class="text-foreground text-lg font-bold">
                                {t('ui.welcome.srs.cramming_title')}
                            </h3>
                        </div>
                        <span
                            class="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-sm font-semibold text-red-700 dark:border-red-900/60 dark:bg-red-950/50 dark:text-red-300"
                        >
                            {t('ui.welcome.srs.cramming_badge')}
                        </span>
                    </div>
                    <p
                        class="text-muted-foreground mb-6 text-sm leading-relaxed"
                    >
                        {t('ui.welcome.srs.cramming_desc')}
                    </p>

                    <div class="space-y-4">
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span>{t('ui.welcome.srs.cramming_day_0')}</span
                                >
                                <span
                                    class="text-foreground font-mono font-semibold"
                                    >{t(
                                        'ui.welcome.srs.cramming_day_0_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="bg-muted h-2.5 w-full overflow-hidden rounded-full"
                            >
                                <div
                                    class="h-full w-full bg-slate-400 dark:bg-slate-500"
                                ></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span>{t('ui.welcome.srs.cramming_day_3')}</span
                                >
                                <span
                                    class="font-mono font-semibold text-red-600 dark:text-red-400"
                                    >{t(
                                        'ui.welcome.srs.cramming_day_3_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="bg-muted h-2.5 w-full overflow-hidden rounded-full"
                            >
                                <div class="h-full w-[35%] bg-red-400"></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span
                                    >{t('ui.welcome.srs.cramming_day_15')}</span
                                >
                                <span
                                    class="font-mono font-semibold text-red-600 dark:text-red-400"
                                    >{t(
                                        'ui.welcome.srs.cramming_day_15_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="bg-muted h-2.5 w-full overflow-hidden rounded-full"
                            >
                                <div class="h-full w-[12%] bg-red-500"></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span
                                    >{t('ui.welcome.srs.cramming_day_30')}</span
                                >
                                <span
                                    class="font-mono font-bold text-red-700 dark:text-red-300"
                                    >{t(
                                        'ui.welcome.srs.cramming_day_30_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="bg-muted h-2.5 w-full overflow-hidden rounded-full"
                            >
                                <div class="h-full w-[14%] bg-red-600"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Card: Vomorize SRS 間隔演算法 (Emerald Green Theme) -->
                <div
                    class="bg-card border-border/90 relative overflow-hidden rounded-2xl border p-6 shadow-xs sm:p-8"
                >
                    <div
                        class="mb-5 flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex size-9 items-center justify-center rounded-xl bg-emerald-100 font-bold text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-400"
                            >
                                <Check class="size-5" />
                            </div>
                            <h3 class="text-foreground text-lg font-bold">
                                {t('ui.welcome.srs.vomorize_title')}
                            </h3>
                        </div>
                        <span
                            class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            {t('ui.welcome.srs.vomorize_badge')}
                        </span>
                    </div>
                    <p
                        class="text-muted-foreground mb-6 text-sm leading-relaxed"
                    >
                        {t('ui.welcome.srs.vomorize_desc')}
                    </p>

                    <div class="space-y-4">
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span
                                    >{t('ui.welcome.srs.vomorize_step_1')}</span
                                >
                                <span
                                    class="font-mono font-semibold text-emerald-700 dark:text-emerald-400"
                                    >{t(
                                        'ui.welcome.srs.vomorize_step_1_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="h-2.5 w-full overflow-hidden rounded-full bg-emerald-100/70 dark:bg-emerald-950/40"
                            >
                                <div
                                    class="h-full w-[98%] bg-emerald-500"
                                ></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span
                                    >{t('ui.welcome.srs.vomorize_step_2')}</span
                                >
                                <span
                                    class="font-mono font-semibold text-emerald-700 dark:text-emerald-400"
                                    >{t(
                                        'ui.welcome.srs.vomorize_step_2_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="h-2.5 w-full overflow-hidden rounded-full bg-emerald-100/70 dark:bg-emerald-950/40"
                            >
                                <div
                                    class="h-full w-[96%] bg-emerald-500"
                                ></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span
                                    >{t('ui.welcome.srs.vomorize_step_3')}</span
                                >
                                <span
                                    class="font-mono font-semibold text-emerald-700 dark:text-emerald-400"
                                    >{t(
                                        'ui.welcome.srs.vomorize_step_3_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="h-2.5 w-full overflow-hidden rounded-full bg-emerald-100/70 dark:bg-emerald-950/40"
                            >
                                <div
                                    class="h-full w-[95%] bg-emerald-600"
                                ></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span
                                    >{t('ui.welcome.srs.vomorize_step_4')}</span
                                >
                                <span
                                    class="font-mono font-bold text-emerald-700 dark:text-emerald-300"
                                    >{t(
                                        'ui.welcome.srs.vomorize_step_4_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="h-2.5 w-full overflow-hidden rounded-full bg-emerald-100/70 dark:bg-emerald-950/40"
                            >
                                <div
                                    class="h-full w-[96%] bg-emerald-600"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5-Step Timeline Sequence -->
            <div
                class="border-border bg-card rounded-2xl border p-6 shadow-xs sm:p-8"
            >
                <h3
                    class="text-foreground mb-6 text-center text-base font-bold sm:text-lg"
                >
                    {t('ui.welcome.srs.timeline_title')}
                </h3>
                <div
                    class="grid grid-cols-1 gap-3.5 sm:grid-cols-3 lg:grid-cols-5"
                >
                    {#each srsTimelineSteps as item (item.step)}
                        <div
                            class={{
                                'flex flex-col items-center rounded-xl border p-4 text-center transition-all': true,
                                'bg-card border-border/80': !item.isFinal,
                                'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900/60 dark:bg-emerald-950/30':
                                    item.isFinal,
                            }}
                        >
                            <span
                                class={{
                                    'mb-2 flex size-7 items-center justify-center rounded-full text-sm font-bold': true,
                                    'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300':
                                        !item.isFinal,
                                    'bg-emerald-600 text-white': item.isFinal,
                                }}
                            >
                                {item.step}
                            </span>
                            <span
                                class={{
                                    'text-sm font-bold': true,
                                    'text-foreground': !item.isFinal,
                                    'text-emerald-800 dark:text-emerald-300':
                                        item.isFinal,
                                }}
                            >
                                {t(item.timeKey)}
                            </span>
                            <span
                                class={{
                                    'mt-1 text-sm leading-snug': true,
                                    'text-muted-foreground': !item.isFinal,
                                    'font-medium text-emerald-700 dark:text-emerald-400':
                                        item.isFinal,
                                }}
                            >
                                {t(item.descKey)}
                            </span>
                        </div>
                    {/each}
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Core Features Section -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" id="features">
        <div class="mx-auto max-w-3xl text-center">
            <h2
                class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl"
            >
                {t('ui.welcome.features.title')}
            </h2>
            <p
                class="text-muted-foreground mt-4 text-base leading-relaxed sm:text-lg"
            >
                {t('ui.welcome.features.subtitle')}
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2">
            {#each features as feature (feature.titleKey)}
                <div
                    class={{
                        'group border-border bg-card relative flex flex-col justify-between rounded-2xl border p-6 shadow-2xs transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md sm:p-8': true,
                        [feature.borderHoverClass]: true,
                    }}
                >
                    <div>
                        <div
                            class={{
                                'flex size-12 items-center justify-center rounded-xl': true,
                                [feature.iconClass]: true,
                            }}
                        >
                            <feature.icon class="size-6" />
                        </div>
                        <h3
                            class="text-foreground mt-5 text-lg font-bold sm:text-xl"
                        >
                            {t(feature.titleKey)}
                        </h3>
                        <p
                            class="text-muted-foreground mt-2.5 text-sm leading-relaxed sm:text-base"
                        >
                            {t(feature.descKey)}
                        </p>
                    </div>

                    <div class="border-border/60 mt-6 border-t pt-4">
                        {#if feature.isQuiz}
                            <div
                                class="flex flex-wrap items-center gap-2.5 text-sm font-medium"
                            >
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                                >
                                    <CheckCircle2
                                        class="size-4 text-emerald-600 dark:text-emerald-400"
                                    />
                                    <span
                                        >{t(
                                            'ui.welcome.features.quiz_correct_pill',
                                        )}</span
                                    >
                                </span>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-2.5 py-1 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300"
                                >
                                    <CircleAlert
                                        class="size-4 text-red-600 dark:text-red-400"
                                    />
                                    <span
                                        >{t(
                                            'ui.welcome.features.quiz_incorrect_pill',
                                        )}</span
                                    >
                                </span>
                            </div>
                        {:else if feature.pillKey}
                            <div
                                class="flex items-center gap-2 text-sm font-medium text-blue-700 dark:text-blue-400"
                            >
                                <Check class="size-4.5" />
                                <span>{t(feature.pillKey)}</span>
                            </div>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    </section>

    <!-- 4. 8-Card Balanced Levels Grid (4 columns x 2 rows, perfectly uniform) -->
    <section
        class="border-border/80 bg-muted/20 border-t py-16 sm:py-24"
        id="levels"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-12 max-w-3xl text-center">
                <h2
                    class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl"
                >
                    {t('ui.welcome.levels.title')}
                </h2>
                <p
                    class="text-muted-foreground mt-4 text-base leading-relaxed sm:text-lg"
                >
                    {t('ui.welcome.levels.subtitle')}
                </p>
            </div>

            <!-- Exactly 8 Cards: 4 columns x 2 rows on lg, all equal height & width -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Cards 1 to 7: Curriculum Levels -->
                {#each levels.slice(0, 7) as level, index (level.id)}
                    {@const parsed = parseLevelInfo(level)}
                    <Link
                        href={`/levels/${level.id}`}
                        class="group border-border bg-card relative flex h-full flex-col justify-between rounded-2xl border p-5 shadow-2xs transition-all duration-300 hover:-translate-y-1 hover:border-blue-400 hover:shadow-md focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:hover:border-blue-600"
                    >
                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <span
                                    class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-sm font-extrabold text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300"
                                >
                                    {t('ui.welcome.levels.level_badge', {
                                        id: level.id,
                                    })}
                                </span>
                            </div>
                            <h3
                                class="text-foreground text-base font-bold transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400"
                            >
                                {parsed.title}
                            </h3>
                            <p
                                class="text-muted-foreground mt-1.5 line-clamp-2 text-sm leading-relaxed"
                            >
                                {parsed.description}
                            </p>
                        </div>

                        <div class="border-border/60 mt-5 border-t pt-3.5">
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span>
                                    {t('ui.welcome.levels.words_count', {
                                        count: (
                                            level.vocabularies_count ?? 1000
                                        ).toLocaleString(),
                                    })} · {t('ui.welcome.levels.groups_count', {
                                        count: level.groups_count ?? 100,
                                    })}
                                </span>
                                <span
                                    class="font-semibold text-emerald-600 dark:text-emerald-400"
                                >
                                    {t('ui.welcome.levels.mastery_rate', {
                                        rate:
                                            levelMasteryPercentages[index] ??
                                            50,
                                    })}
                                </span>
                            </div>
                            <div
                                class="bg-muted mb-3.5 h-2 w-full overflow-hidden rounded-full"
                            >
                                <div
                                    class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                    style={`width: ${levelMasteryPercentages[index] ?? 50}%`}
                                ></div>
                            </div>
                            <div
                                class="flex items-center justify-between text-sm font-bold text-blue-600 dark:text-blue-400"
                            >
                                <span
                                    >{t(
                                        'ui.welcome.levels.explore_level',
                                    )}</span
                                >
                                <ArrowRight
                                    class="size-4 transition-transform duration-300 group-hover:translate-x-1"
                                />
                            </div>
                        </div>
                    </Link>
                {/each}

                <!-- Card 8: Dedicated Overview Navigation Card -->
                <Link
                    href="/levels"
                    class="group to-card dark:to-card relative flex h-full flex-col justify-between rounded-2xl border-2 border-dashed border-blue-200 bg-gradient-to-b from-blue-50/70 p-5 shadow-2xs transition-all duration-300 hover:-translate-y-1 hover:border-blue-400 hover:shadow-md focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:border-blue-900/60 dark:from-blue-950/20 dark:hover:border-blue-600"
                >
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <span
                                class="inline-flex items-center rounded-md bg-blue-600 px-2.5 py-1 text-sm font-extrabold text-white shadow-2xs shadow-blue-500/20"
                            >
                                {t('ui.welcome.levels.browse_all_badge')}
                            </span>
                        </div>
                        <h3
                            class="text-foreground text-base font-bold transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400"
                        >
                            {t('ui.welcome.levels.browse_all_title')}
                        </h3>
                        <p
                            class="text-muted-foreground mt-1.5 text-sm leading-relaxed"
                        >
                            {t('ui.welcome.levels.browse_all_desc')}
                        </p>
                    </div>

                    <div
                        class="mt-5 border-t border-blue-100 pt-3.5 dark:border-blue-950/60"
                    >
                        <div
                            class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                        >
                            <span
                                >{t('ui.welcome.levels.browse_all_stats')}</span
                            >
                            <span
                                class="font-semibold text-blue-600 dark:text-blue-400"
                            >
                                {t('ui.welcome.levels.browse_all_complete')}
                            </span>
                        </div>
                        <div
                            class="mb-3.5 h-2 w-full overflow-hidden rounded-full bg-blue-100 dark:bg-blue-950/50"
                        >
                            <div
                                class="h-full w-full rounded-full bg-blue-600"
                            ></div>
                        </div>
                        <div
                            class="flex items-center justify-between rounded-xl bg-blue-600 px-3.5 py-2.5 text-sm font-bold text-white shadow-2xs transition-colors group-hover:bg-blue-700"
                        >
                            <span
                                >{t(
                                    'ui.welcome.levels.browse_all_action',
                                )}</span
                            >
                            <ArrowRight
                                class="size-4 transition-transform duration-300 group-hover:translate-x-1"
                            />
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </section>

    <!-- 5. Bottom CTA Section -->
    <section class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8" id="demo">
        <div
            class="relative overflow-hidden rounded-3xl bg-blue-600 p-8 text-center text-white shadow-xl sm:p-14 dark:bg-blue-700"
        >
            <div class="relative z-10 mx-auto max-w-2xl">
                <h2
                    class="text-3xl leading-tight font-extrabold tracking-tight sm:text-4xl md:text-5xl"
                >
                    {t('ui.welcome.cta.title')}
                </h2>
                <p
                    class="mt-4 text-base leading-relaxed text-blue-100 sm:text-lg"
                >
                    {t('ui.welcome.cta.subtitle')}
                </p>

                <div
                    class="mt-8 flex flex-col items-center justify-center gap-3.5 sm:flex-row"
                >
                    <Button
                        asChild
                        size="lg"
                        class={cn(
                            'w-full bg-white text-base font-bold text-blue-600 shadow-lg transition-all hover:bg-blue-50 hover:shadow-xl sm:w-auto',
                        )}
                    >
                        {#snippet children(props)}
                            <Link href="/levels/1" class={props.class}>
                                <span>{t('ui.welcome.cta.start_now')}</span>
                                <ArrowRight class="size-4.5" />
                            </Link>
                        {/snippet}
                    </Button>
                </div>

                <div
                    class="mt-6 flex items-center justify-center gap-2 text-sm font-medium text-blue-100"
                >
                    <ShieldCheck class="size-4.5 text-emerald-300" />
                    <span>{t('ui.welcome.cta.free_note')}</span>
                </div>
            </div>
        </div>
    </section>
</main>
