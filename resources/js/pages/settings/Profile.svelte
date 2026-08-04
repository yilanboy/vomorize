<script module lang="ts">
    import { translations } from '@/lib/locale.svelte';
    import { edit } from '@/routes/profile';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteUser from '@/components/DeleteUser.svelte';
    import InputError from '@/components/InputError.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { send } from '@/routes/verification';

    const user = $derived(page.props.auth.user);
    let t = $derived(translations());
</script>

<AppHead title={t['profile_settings']} />

<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-xs sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-6 space-y-1">
        <h2 class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-50">
                {t['profile_settings']}
            </h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {t['profile_subtitle']}
            </p>
        </div>

        <Form
            {...ProfileController.update.form()}
            class="space-y-5"
            options={{ preserveScroll: true }}
        >
            {#snippet children({ errors, processing })}
                <div class="grid gap-2.5">
                    <Label for="name">
                        {t['name']}
                    </Label>
                    <Input
                        id="name"
                        name="name"
                        value={user.name}
                        required
                        autocomplete="name"
                        placeholder={t['full_name']}
                    />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2.5">
                    <Label for="email">
                        {t['email']}
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        value={user.email}
                        required
                        autocomplete="username"
                        placeholder={t['email']}
                    />
                    <InputError message={errors.email} />
                </div>

                {#if Boolean(page.props.mustVerifyEmail) && !user.email_verified_at}
                    <div
                        class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-800"
                    >
                        <p class="font-medium">您的電子郵件地址尚未驗證。</p>
                        <TextLink
                            href={send()}
                            as="button"
                            class="mt-1 font-semibold text-amber-900 underline hover:text-amber-950"
                        >
                            點擊此處重新發送驗證郵件。
                        </TextLink>

                        {#if page.props.status === 'verification-link-sent'}
                            <div class="mt-2 text-sm font-semibold text-emerald-700">
                                {t['verification_link_sent']}
                            </div>
                        {/if}
                    </div>
                {/if}

                <div class="pt-2">
                    <Button type="submit" disabled={processing} data-test="update-profile-button">
                        {processing ? t['saving'] : t['save_changes']}
                    </Button>
                </div>
            {/snippet}
        </Form>
    </div>

    <DeleteUser />
</div>
