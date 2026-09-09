<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import BookOpen from '@lucide/svelte/icons/book-open';
    import Brain from '@lucide/svelte/icons/brain';
    import Check from '@lucide/svelte/icons/check';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import CircleAlert from '@lucide/svelte/icons/circle-alert';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
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
    import { toUrl } from '@/lib/utils';
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
    let isPlayingAudio = $state(false);
    function playDemoAudio() {
        if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
            isPlayingAudio = true;
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(
                t('ui.welcome.hero.demo_word'),
            );
            utterance.lang = 'en-US';
            utterance.rate = 0.9;
            utterance.onend = () => {
                isPlayingAudio = false;
            };
            utterance.onerror = () => {
                isPlayingAudio = false;
            };
            window.speechSynthesis.speak(utterance);
        }
    }

    // Demo card action state
    let demoState = $state<'initial' | 'mastered' | 'forgot'>('initial');

    // Sample mastery percentages for levels preview on landing page
    const levelMasteryPercentages = [70, 55, 40, 25, 15, 10, 5];

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
                'hover:border-blue-400 dark:hover:border-blue-600',
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
                'hover:border-blue-400 dark:hover:border-blue-600',
            isQuiz: false,
        },
        {
            icon: Target,
            titleKey: 'ui.welcome.features.quiz_title',
            descKey: 'ui.welcome.features.quiz_desc',
            iconClass:
                'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400',
            borderHoverClass:
                'hover:border-emerald-400 dark:hover:border-emerald-600',
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
                'hover:border-slate-400 dark:hover:border-slate-600',
            isQuiz: false,
        },
    ];
</script>

<AppHead title={t('ui.welcome.hero.title')} />

<div class="space-y-20 py-8 sm:space-y-28 sm:py-16">
    <!-- 1. Hero Section -->
    <section class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div
            class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12 lg:gap-8"
        >
            <!-- Left Hero Content -->
            <div class="flex flex-col items-start text-left lg:col-span-7">
                <!-- Pill Badge -->
                <div
                    class="border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-400 inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-semibold tracking-wide"
                >
                    <Sparkles class="size-4" />
                    <span>{t('ui.welcome.hero.badge')}</span>
                </div>

                <!-- Main Headline -->
                <h1
                    class="text-foreground mt-6 text-3xl font-extrabold tracking-tight sm:text-4xl md:text-5xl lg:leading-[1.15]"
                >
                    {t('ui.welcome.hero.title')}
                </h1>

                <!-- Subheadline -->
                <p
                    class="text-muted-foreground mt-5 max-w-xl text-base leading-relaxed sm:text-lg"
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
                        class="bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20 w-full sm:w-auto text-base font-semibold"
                    >
                        {#snippet children(props)}
                            <Link href="/levels" class={props.class}>
                                <span>{t('ui.welcome.hero.start_learning')}</span>
                                <ArrowRight class="size-4.5" />
                            </Link>
                        {/snippet}
                    </Button>

                    <Button
                        asChild
                        variant="outline"
                        size="lg"
                        class="w-full text-base sm:w-auto"
                    >
                        {#snippet children(props)}
                            <a href="#levels" class={props.class}>
                                <span>{t('ui.welcome.hero.explore_levels')}</span>
                            </a>
                        {/snippet}
                    </Button>

                    {#if auth.user}
                        <Button
                            asChild
                            variant="ghost"
                            size="lg"
                            class="w-full text-base sm:w-auto"
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
                            class="w-full text-base sm:w-auto"
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
                        class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 font-semibold"
                    >
                        <CheckCircle2 class="size-4.5" />
                        <span>{t('ui.welcome.hero.free_notice')}</span>
                    </span>
                </div>
            </div>

            <!-- Right Hero Product Visual / Interactive Flashcard -->
            <div class="relative flex justify-center lg:col-span-5">
                <!-- Pure Blue & Fresh Emerald Glow Highlights -->
                <div
                    class="pointer-events-none absolute -top-6 -right-6 -z-10 size-64 rounded-full bg-blue-500/10 blur-3xl"
                ></div>
                <div
                    class="pointer-events-none absolute -bottom-8 -left-6 -z-10 size-64 rounded-full bg-emerald-500/10 blur-3xl"
                ></div>

                <!-- Flashcard Container -->
                <div
                    class="border-border bg-card relative w-full max-w-[390px] rounded-3xl border p-6 shadow-xl transition-all"
                >
                    <!-- Header Preview -->
                    <div
                        class="border-border/60 text-muted-foreground mb-4 flex items-center justify-between border-b pb-3 text-sm font-medium"
                    >
                        <span
                            class="text-foreground flex items-center gap-1.5 font-semibold"
                        >
                            <BookOpen
                                class="text-blue-600 dark:text-blue-400 size-4.5"
                            />
                            <span>Vomorize</span>
                        </span>
                        <div class="flex items-center gap-2">
                            <span
                                class="bg-muted text-muted-foreground rounded-full px-2.5 py-0.5 font-mono text-sm"
                            >
                                {t('ui.welcome.hero.demo_word_counter', {
                                    current: 3,
                                    total: 20,
                                })}
                            </span>
                            <button
                                type="button"
                                onclick={playDemoAudio}
                                class={{
                                    'text-muted-foreground hover:text-foreground rounded-full p-1 transition-colors': true,
                                    'text-blue-600 animate-pulse': isPlayingAudio,
                                }}
                                title={t('ui.welcome.hero.play_audio')}
                            >
                                <Volume2 class="size-4.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Flashcard Surface -->
                    <div
                        class="border-border/80 bg-muted/40 relative flex flex-col rounded-2xl border p-5 text-left"
                    >
                        <!-- Tag Row -->
                        <div class="mb-3 flex w-full items-center justify-between">
                            <span
                                class="border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/50 dark:text-blue-300 rounded-md border px-2.5 py-0.5 text-sm font-bold uppercase tracking-wide"
                            >
                                {t('ui.welcome.hero.demo_pos')}
                            </span>
                            <span
                                class="text-muted-foreground text-sm font-medium"
                            >
                                {t('ui.welcome.hero.demo_preview_tag')}
                            </span>
                        </div>

                        <!-- Main Target Word -->
                        <div class="mb-1 flex items-baseline gap-2.5">
                            <h2
                                class="text-foreground text-3xl font-extrabold tracking-tight"
                            >
                                {t('ui.welcome.hero.demo_word')}
                            </h2>
                            <button
                                type="button"
                                onclick={playDemoAudio}
                                class={{
                                    'flex size-6 items-center justify-center rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-950/70 dark:text-blue-300 transition-colors': true,
                                    'ring-2 ring-blue-500 ring-offset-1': isPlayingAudio,
                                }}
                                title={t('ui.welcome.hero.play_audio')}
                            >
                                <Volume2 class="size-3.5" />
                            </button>
                        </div>
                        <p
                            class="text-muted-foreground mb-4 font-mono text-sm italic"
                        >
                            {t('ui.welcome.hero.demo_phonetic')}
                        </p>

                        <!-- Meaning -->
                        <div class="border-border/60 w-full border-t pt-3 pb-3">
                            <span
                                class="text-muted-foreground mb-1 block text-sm font-bold uppercase tracking-wider"
                            >
                                {t('ui.welcome.hero.demo_definition_label')}
                            </span>
                            <p class="text-foreground text-base font-bold">
                                {t('ui.welcome.hero.demo_translation')}
                            </p>
                            <p
                                class="text-muted-foreground mt-1 text-sm leading-relaxed"
                            >
                                {t('ui.welcome.hero.demo_definition')}
                            </p>
                        </div>

                        <!-- Contextual Example -->
                        <div
                            class="border-border bg-card shadow-2xs mb-4 w-full rounded-xl border p-3.5"
                        >
                            <span
                                class="text-muted-foreground mb-1 block text-sm font-bold uppercase tracking-wider"
                            >
                                {t('ui.welcome.hero.demo_example_label')}
                            </span>
                            <p class="text-foreground text-sm leading-relaxed">
                                Her natural <span
                                    class="text-blue-600 dark:text-blue-400 font-bold underline decoration-blue-300 decoration-2 underline-offset-2"
                                    >resilience</span
                                >
                                helped her overcome immense challenges.
                            </p>
                        </div>

                        <!-- Mastery Status Badge (Emerald Green) -->
                        <div
                            class="border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300 flex items-center justify-center gap-1.5 rounded-xl border px-3.5 py-2 text-sm font-medium"
                        >
                            <CheckCircle2
                                class="text-emerald-600 dark:text-emerald-400 size-4"
                            />
                            <span>{t('ui.welcome.hero.demo_srs_badge')}</span>
                        </div>
                    </div>

                    <!-- Action Row -->
                    <div class="mt-4 grid grid-cols-2 gap-3 pt-1">
                        <button
                            type="button"
                            onclick={() => (demoState = 'forgot')}
                            class={{
                                'flex items-center justify-center gap-1.5 rounded-xl px-3.5 py-2.5 text-sm font-semibold transition-colors': true,
                                'bg-secondary hover:bg-secondary/80 text-secondary-foreground': demoState !== 'forgot',
                                'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300 ring-2 ring-red-500': demoState === 'forgot',
                            }}
                        >
                            <RotateCcw class="text-muted-foreground size-4" />
                            <span>{t('ui.welcome.hero.demo_forgot')}</span>
                        </button>
                        <button
                            type="button"
                            onclick={() => (demoState = 'mastered')}
                            class={{
                                'shadow-2xs flex items-center justify-center gap-1.5 rounded-xl px-3.5 py-2.5 text-sm font-bold transition-colors': true,
                                'bg-emerald-600 hover:bg-emerald-700 text-white': demoState !== 'mastered',
                                'bg-emerald-700 text-white ring-2 ring-emerald-400': demoState === 'mastered',
                            }}
                        >
                            <Check class="size-4" />
                            <span>{t('ui.welcome.hero.demo_mastered')}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. SRS Cognitive Science / Ebbinghaus Comparison Section -->
    <section
        class="border-border/80 bg-card/40 relative border-y py-16 sm:py-24"
        id="srs-science"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-14 max-w-3xl text-center sm:mb-16">
                <span
                    class="border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-400 inline-block rounded-full border px-3.5 py-1.5 text-sm font-bold uppercase tracking-wider"
                >
                    {t('ui.welcome.srs.badge')}
                </span>
                <h2
                    class="text-foreground mt-4 text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl"
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
                <!-- Left Card: 傳統死記硬背 (Red Alert Theme) -->
                <div
                    class="border-red-200/90 bg-red-50/40 dark:border-red-950/60 dark:bg-red-950/20 relative overflow-hidden rounded-2xl border p-6 sm:p-8"
                >
                    <div
                        class="mb-5 flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex size-8 items-center justify-center rounded-lg bg-red-100 font-bold text-red-600 dark:bg-red-950/70 dark:text-red-400"
                            >
                                <TriangleAlert class="size-4.5" />
                            </div>
                            <h3 class="text-foreground text-lg font-bold">
                                {t('ui.welcome.srs.cramming_title')}
                            </h3>
                        </div>
                        <span
                            class="border-red-200 bg-red-100 text-red-700 dark:border-red-900/60 dark:bg-red-950/50 dark:text-red-300 rounded-full border px-3 py-1 text-sm font-semibold"
                        >
                            {t('ui.welcome.srs.cramming_badge')}
                        </span>
                    </div>
                    <p class="text-muted-foreground mb-6 text-sm leading-relaxed">
                        {t('ui.welcome.srs.cramming_desc')}
                    </p>

                    <div class="space-y-4">
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span>{t('ui.welcome.srs.cramming_day_0')}</span>
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
                                <div class="bg-slate-400 h-full w-full"></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span>{t('ui.welcome.srs.cramming_day_3')}</span>
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
                                <div class="bg-red-400 h-full w-[35%]"></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span>{t('ui.welcome.srs.cramming_day_15')}</span>
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
                                <div class="bg-red-500 h-full w-[12%]"></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span>{t('ui.welcome.srs.cramming_day_30')}</span>
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
                                <div class="bg-red-600 h-full w-[14%]"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Card: Vomorize SRS 間隔演算法 (Emerald Green Theme) -->
                <div
                    class="border-emerald-200/90 bg-emerald-50/40 dark:border-emerald-950/60 dark:bg-emerald-950/20 relative overflow-hidden rounded-2xl border p-6 sm:p-8"
                >
                    <div
                        class="mb-5 flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 font-bold text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-400"
                            >
                                <Check class="size-4.5" />
                            </div>
                            <h3 class="text-foreground text-lg font-bold">
                                {t('ui.welcome.srs.vomorize_title')}
                            </h3>
                        </div>
                        <span
                            class="border-emerald-200 bg-emerald-100 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/50 dark:text-emerald-300 rounded-full border px-3 py-1 text-sm font-semibold"
                        >
                            {t('ui.welcome.srs.vomorize_badge')}
                        </span>
                    </div>
                    <p class="text-muted-foreground mb-6 text-sm leading-relaxed">
                        {t('ui.welcome.srs.vomorize_desc')}
                    </p>

                    <div class="space-y-4">
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span>{t('ui.welcome.srs.vomorize_step_1')}</span>
                                <span
                                    class="text-emerald-700 dark:text-emerald-400 font-mono font-semibold"
                                    >{t(
                                        'ui.welcome.srs.vomorize_step_1_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="bg-emerald-100/70 dark:bg-emerald-950/40 h-2.5 w-full overflow-hidden rounded-full"
                            >
                                <div class="bg-emerald-500 h-full w-[98%]"></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span>{t('ui.welcome.srs.vomorize_step_2')}</span>
                                <span
                                    class="text-emerald-700 dark:text-emerald-400 font-mono font-semibold"
                                    >{t(
                                        'ui.welcome.srs.vomorize_step_2_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="bg-emerald-100/70 dark:bg-emerald-950/40 h-2.5 w-full overflow-hidden rounded-full"
                            >
                                <div class="bg-emerald-500 h-full w-[96%]"></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span>{t('ui.welcome.srs.vomorize_step_3')}</span>
                                <span
                                    class="text-emerald-700 dark:text-emerald-400 font-mono font-semibold"
                                    >{t(
                                        'ui.welcome.srs.vomorize_step_3_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="bg-emerald-100/70 dark:bg-emerald-950/40 h-2.5 w-full overflow-hidden rounded-full"
                            >
                                <div class="bg-emerald-600 h-full w-[95%]"></div>
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-muted-foreground mb-1.5 flex items-center justify-between text-sm"
                            >
                                <span>{t('ui.welcome.srs.vomorize_step_4')}</span>
                                <span
                                    class="text-emerald-700 dark:text-emerald-300 font-mono font-bold"
                                    >{t(
                                        'ui.welcome.srs.vomorize_step_4_val',
                                    )}</span
                                >
                            </div>
                            <div
                                class="bg-emerald-100/70 dark:bg-emerald-950/40 h-2.5 w-full overflow-hidden rounded-full"
                            >
                                <div class="bg-emerald-600 h-full w-[96%]"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5-Step Timeline Sequence -->
            <div class="border-border bg-card rounded-2xl border p-6 sm:p-8">
                <h4
                    class="text-muted-foreground mb-6 text-center text-sm font-bold uppercase tracking-wider"
                >
                    {t('ui.welcome.srs.timeline_title')}
                </h4>
                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3 lg:grid-cols-5">
                    {#each srsTimelineSteps as item (item.step)}
                        <div
                            class={{
                                'flex flex-col items-center rounded-xl border p-4 text-center transition-all': true,
                                'bg-card border-border': !item.isFinal,
                                'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900/60 dark:bg-emerald-950/30':
                                    item.isFinal,
                            }}
                        >
                            <span
                                class={{
                                    'size-7 rounded-full flex items-center justify-center text-sm font-bold mb-2': true,
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
                                    'text-sm mt-1 leading-snug': true,
                                    'text-muted-foreground': !item.isFinal,
                                    'text-emerald-700 dark:text-emerald-400 font-medium':
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
            <span
                class="border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-400 inline-block rounded-full border px-3.5 py-1.5 text-sm font-bold uppercase tracking-wider"
            >
                {t('ui.welcome.features.badge')}
            </span>
            <h2
                class="text-foreground mt-4 text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl"
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
                                    class="border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300 inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1"
                                >
                                    <CheckCircle2
                                        class="text-emerald-600 dark:text-emerald-400 size-4"
                                    />
                                    <span
                                        >{t(
                                            'ui.welcome.features.quiz_correct_pill',
                                        )}</span
                                    >
                                </span>
                                <span
                                    class="border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300 inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1"
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
                                class="text-blue-700 dark:text-blue-400 flex items-center gap-2 text-sm font-medium"
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

    <!-- 4. 8-Card Balanced Levels Grid (4 columns x 2 rows, perfectly uniform, NO CEFR) -->
    <section
        class="border-border/80 bg-muted/20 border-t py-16 sm:py-24"
        id="levels"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-12 max-w-3xl text-center">
                <span
                    class="border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-400 inline-block rounded-full border px-3.5 py-1.5 text-sm font-bold uppercase tracking-wider"
                >
                    {t('ui.welcome.levels.badge')}
                </span>
                <h2
                    class="text-foreground mt-4 text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl"
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
                    <Link
                        href={`/levels/${level.id}`}
                        class="group border-border bg-card relative flex h-full flex-col justify-between rounded-2xl border p-5 shadow-2xs transition-all duration-300 hover:-translate-y-1 hover:border-blue-400 hover:shadow-md dark:hover:border-blue-600"
                    >
                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <span
                                    class="border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300 inline-flex items-center rounded-md border px-2.5 py-1 text-sm font-extrabold"
                                >
                                    {t('ui.welcome.levels.level_badge', {
                                        id: level.id,
                                    })}
                                </span>
                            </div>
                            <h4
                                class="text-foreground text-base font-bold transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400"
                            >
                                {level.name}
                            </h4>
                            <p
                                class="text-muted-foreground mt-1.5 line-clamp-2 text-sm leading-relaxed"
                            >
                                {level.description}
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
                    class="group to-card dark:to-card relative flex h-full flex-col justify-between rounded-2xl border-2 border-dashed border-blue-200 bg-gradient-to-b from-blue-50/70 p-5 shadow-2xs transition-all duration-300 hover:-translate-y-1 hover:border-blue-400 hover:shadow-md dark:border-blue-900/60 dark:from-blue-950/20 dark:hover:border-blue-600"
                >
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <span
                                class="inline-flex items-center rounded-md bg-blue-600 px-2.5 py-1 text-sm font-extrabold text-white shadow-2xs shadow-blue-500/20"
                            >
                                {t('ui.welcome.levels.browse_all_badge')}
                            </span>
                        </div>
                        <h4
                            class="text-foreground text-base font-bold transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400"
                        >
                            {t('ui.welcome.levels.browse_all_title')}
                        </h4>
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
            <!-- Pure Blue / Subtle White Ambient Highlights -->
            <div
                class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-white/10 blur-2xl"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-24 -left-24 size-72 rounded-full bg-blue-900/40 blur-2xl"
            ></div>

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

                <div class="mt-8 flex flex-col items-center justify-center gap-3.5 sm:flex-row">
                    <Button
                        asChild
                        size="lg"
                        class="w-full bg-white text-base font-bold text-blue-600 shadow-lg transition-all hover:bg-blue-50 hover:shadow-xl sm:w-auto"
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
</div>
