import { cn } from '@/lib/utils';

export { default as NavigationMenu } from './NavigationMenu.svelte';
export { default as NavigationMenuItem } from './NavigationMenuItem.svelte';
export { default as NavigationMenuLink } from './NavigationMenuLink.svelte';
export { default as NavigationMenuList } from './NavigationMenuList.svelte';

export function navigationMenuTriggerStyle(className = '') {
    return cn(
        'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-zinc-200 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-50 focus-visible:ring-2 focus-visible:ring-zinc-400 dark:focus-visible:ring-zinc-600 focus-visible:ring-offset-2 focus-visible:outline-none',
        className,
    );
}
