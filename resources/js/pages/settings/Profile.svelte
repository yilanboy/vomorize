<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import User from '@lucide/svelte/icons/user';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteUser from '@/components/DeleteUser.svelte';
    import InputError from '@/components/InputError.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';
    import { send } from '@/routes/verification';

    const user = $derived(page.props.auth.user);
</script>

<AppHead title={t('ui.settings.profile.breadcrumbs')} />

<h1 class="sr-only">{t('ui.settings.profile.breadcrumbs')}</h1>

<div class="space-y-8">
    <!-- Profile Information Card -->
    <div
        class="border-border/80 bg-card rounded-2xl border p-6 shadow-xs sm:p-7"
    >
        <header class="mb-6 flex items-start gap-3">
            <div
                class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl"
            >
                <User class="size-5" />
            </div>
            <div>
                <h2
                    class="text-foreground text-lg font-semibold tracking-tight"
                >
                    {t('ui.settings.profile.title')}
                </h2>
                <p class="text-muted-foreground mt-0.5 text-sm">
                    {t('ui.settings.profile.subtitle')}
                </p>
            </div>
        </header>

        <Form
            {...ProfileController.update.form()}
            class="space-y-5"
            options={{ preserveScroll: true }}
        >
            {#snippet children({ errors, processing })}
                <div class="grid gap-1.5">
                    <Label
                        for="name"
                        class="text-foreground text-sm font-semibold"
                    >
                        {t('ui.settings.profile.name')}
                    </Label>
                    <Input
                        id="name"
                        name="name"
                        class="h-12 w-full rounded-xl text-sm"
                        value={user.name}
                        required
                        autocomplete="name"
                        placeholder={t('ui.settings.profile.name_placeholder')}
                    />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-1.5">
                    <Label
                        for="email"
                        class="text-foreground text-sm font-semibold"
                    >
                        {t('ui.settings.profile.email')}
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        class="h-12 w-full rounded-xl text-sm"
                        value={user.email}
                        required
                        autocomplete="username"
                        placeholder={t('ui.settings.profile.email_placeholder')}
                    />
                    <InputError message={errors.email} />
                </div>

                {#if Boolean(page.props.mustVerifyEmail) && !user.email_verified_at}
                    <div
                        class="rounded-xl border border-amber-500/20 bg-amber-50/70 p-3.5 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-300"
                    >
                        <p>
                            {t('ui.settings.profile.email_unverified')}
                            <TextLink
                                href={send()}
                                as="button"
                                class="ml-1 font-semibold underline hover:text-amber-950 dark:hover:text-amber-200"
                            >
                                {t('ui.settings.profile.resend_verification')}
                            </TextLink>
                        </p>

                        {#if page.props.status === 'verification-link-sent'}
                            <div
                                class="mt-2 font-medium text-emerald-600 dark:text-emerald-400"
                            >
                                {t('ui.settings.profile.verification_sent')}
                            </div>
                        {/if}
                    </div>
                {/if}

                <div class="pt-2">
                    <Button
                        type="submit"
                        class="h-11 rounded-xl px-6 text-sm font-semibold shadow-xs transition-all active:scale-[0.99]"
                        disabled={processing}
                        data-test="update-profile-button"
                    >
                        {#if processing}
                            <Spinner class="size-4" />
                        {/if}
                        {t('ui.settings.profile.save')}
                    </Button>
                </div>
            {/snippet}
        </Form>
    </div>

    <!-- Danger Zone / Delete Account -->
    <DeleteUser />
</div>
