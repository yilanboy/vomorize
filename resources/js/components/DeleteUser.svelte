<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AlertTriangle from '@lucide/svelte/icons/alert-triangle';
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
    import { translations } from '@/lib/locale.svelte';

    let t = $derived(translations());
</script>

<div
    class="rounded-2xl border border-red-600/30 bg-zinc-50 p-6 shadow-xs sm:p-8 dark:border-red-400/30 dark:bg-zinc-900"
>
    <div class="mb-4 space-y-1">
        <h2
            class="text-lg font-bold tracking-tight text-red-600 dark:text-red-400"
        >
            {t['delete_account']}
        </h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {t['delete_account_subtitle']}
        </p>
    </div>

    <div
        class="rounded-xl border border-red-600/30 bg-red-600/10 p-4 text-sm dark:border-red-400/30 dark:bg-red-400/10"
    >
        <div class="flex items-start gap-3">
            <AlertTriangle
                class="mt-0.5 h-5 w-5 shrink-0 text-red-600 dark:text-red-400"
            />
            <div class="space-y-1 text-red-600 dark:text-red-400">
                <p class="font-semibold">
                    {t['delete_account_warning_title']}
                </p>
                <p class="leading-relaxed text-red-600/80 dark:text-red-400/80">
                    {t['delete_account_warning_desc']}
                </p>
            </div>
        </div>

        <div class="mt-4">
            <Dialog>
                <DialogTrigger>
                    <Button
                        variant="destructive"
                        data-test="delete-user-button"
                    >
                        {t['delete_account']}
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-md">
                    <Form
                        {...ProfileController.destroy.form()}
                        class="space-y-6"
                        options={{ preserveScroll: true }}
                    >
                        {#snippet children({ errors, processing })}
                            <div class="space-y-3">
                                <DialogTitle
                                    class="text-lg font-bold text-zinc-900 dark:text-zinc-50"
                                >
                                    {t['confirm_delete_account_title']}
                                </DialogTitle>
                                <DialogDescription
                                    class="text-sm text-zinc-500 dark:text-zinc-400"
                                >
                                    {t['confirm_delete_account_desc']}
                                </DialogDescription>
                            </div>

                            <div class="grid gap-2.5">
                                <Label for="password" class="sr-only">
                                    {t['password']}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    placeholder={t['enter_password_confirm']}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <DialogFooter class="gap-2">
                                <DialogClose>
                                    <Button variant="secondary">
                                        {t['cancel']}
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                    data-test="confirm-delete-user-button"
                                >
                                    {processing
                                        ? t['deleting']
                                        : t['confirm_delete_account']}
                                </Button>
                            </DialogFooter>
                        {/snippet}
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</div>
