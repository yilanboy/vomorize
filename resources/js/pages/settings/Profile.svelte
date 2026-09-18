<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteUser from '@/components/DeleteUser.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { t } from '@/lib/i18n';
    import { send } from '@/routes/verification';

    const user = $derived(page.props.auth.user);
</script>

<AppHead title={t('ui.settings.profile.breadcrumbs')} />

<h1 class="sr-only">{t('ui.settings.profile.breadcrumbs')}</h1>

<div class="flex flex-col space-y-6">
    <Heading
        variant="small"
        title={t('ui.settings.profile.title')}
        description={t('ui.settings.profile.subtitle')}
    />

    <Form
        {...ProfileController.update.form()}
        class="space-y-6"
        options={{ preserveScroll: true }}
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="name">{t('ui.settings.profile.name')}</Label>
                <Input
                    id="name"
                    name="name"
                    class="mt-1 block w-full"
                    value={user.name}
                    required
                    autocomplete="name"
                    placeholder={t('ui.settings.profile.name_placeholder')}
                />
                <InputError class="mt-2" message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="email">{t('ui.settings.profile.email')}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    class="mt-1 block w-full"
                    value={user.email}
                    required
                    autocomplete="username"
                    placeholder={t('ui.settings.profile.email_placeholder')}
                />
                <InputError class="mt-2" message={errors.email} />
            </div>

            {#if Boolean(page.props.mustVerifyEmail) && !user.email_verified_at}
                <div>
                    <p class="text-muted-foreground -mt-4 text-sm">
                        {t('ui.settings.profile.email_unverified')}
                        <TextLink href={send()} as="button">
                            {t('ui.settings.profile.resend_verification')}
                        </TextLink>
                    </p>

                    {#if page.props.status === 'verification-link-sent'}
                        <div class="mt-2 text-sm font-medium text-green-600">
                            {t('ui.settings.profile.verification_sent')}
                        </div>
                    {/if}
                </div>
            {/if}

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    disabled={processing}
                    data-test="update-profile-button"
                >
                    {t('ui.settings.profile.save')}
                </Button>
            </div>
        {/snippet}
    </Form>
</div>

<DeleteUser />
