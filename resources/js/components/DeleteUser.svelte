<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import TriangleAlert from '@lucide/svelte/icons/triangle-alert';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';
</script>

<div
    class="border-destructive/20 bg-destructive/5 dark:border-destructive/30 dark:bg-destructive/10 rounded-2xl border p-6 shadow-xs sm:p-7"
>
    <header class="mb-5 flex items-start gap-3">
        <div
            class="bg-destructive/15 text-destructive flex size-10 shrink-0 items-center justify-center rounded-xl"
        >
            <TriangleAlert class="size-5" />
        </div>
        <div>
            <h2 class="text-foreground text-lg font-semibold tracking-tight">
                {t('ui.settings.profile.delete_account.title')}
            </h2>
            <p class="text-muted-foreground mt-0.5 text-sm">
                {t('ui.settings.profile.delete_account.subtitle')}
            </p>
        </div>
    </header>

    <div class="space-y-4">
        <p
            class="text-destructive/90 dark:text-destructive text-sm leading-relaxed"
        >
            {t('ui.settings.profile.delete_account.warning_desc')}
        </p>

        <Dialog>
            <DialogTrigger>
                {#snippet child({ props })}
                    <Button
                        variant="destructive"
                        class="h-11 rounded-xl px-5 text-sm font-semibold shadow-xs transition-all active:scale-[0.99]"
                        data-test="delete-user-button"
                        {...props}
                    >
                        {t('ui.settings.profile.delete_account.button')}
                    </Button>
                {/snippet}
            </DialogTrigger>
            <DialogContent class="rounded-2xl p-6 sm:max-w-md sm:p-7">
                <Form
                    {...ProfileController.destroy.form()}
                    class="space-y-5"
                    options={{ preserveScroll: true }}
                >
                    {#snippet children({ errors, processing })}
                        <div class="space-y-2">
                            <DialogTitle
                                class="text-foreground text-xl font-bold tracking-tight"
                            >
                                {t(
                                    'ui.settings.profile.delete_account.dialog_title',
                                )}
                            </DialogTitle>
                            <DialogDescription
                                class="text-muted-foreground text-sm leading-relaxed"
                            >
                                {t(
                                    'ui.settings.profile.delete_account.dialog_desc',
                                )}
                            </DialogDescription>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="password" class="sr-only">
                                {t(
                                    'ui.settings.profile.delete_account.password',
                                )}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder={t(
                                    'ui.settings.profile.delete_account.password_placeholder',
                                )}
                                class="h-12 rounded-xl text-sm"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <DialogFooter
                            class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                        >
                            <DialogClose>
                                {#snippet child({ props })}
                                    <Button
                                        variant="outline"
                                        class="h-11 w-full rounded-xl text-sm font-medium sm:w-auto"
                                        {...props}
                                    >
                                        {t(
                                            'ui.settings.profile.delete_account.cancel',
                                        )}
                                    </Button>
                                {/snippet}
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                class="h-11 w-full rounded-xl text-sm font-semibold sm:w-auto"
                                disabled={processing}
                                data-test="confirm-delete-user-button"
                            >
                                {#if processing}
                                    <Spinner class="size-4" />
                                {/if}
                                {t(
                                    'ui.settings.profile.delete_account.confirm',
                                )}
                            </Button>
                        </DialogFooter>
                    {/snippet}
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</div>
