import type { LinkComponentBaseProps } from '@inertiajs/core';
export { cn } from "cn";

export function toUrl(
    href: NonNullable<LinkComponentBaseProps['href']>,
): string {
    return typeof href === 'string' ? href : href.url;
}

export type WithoutChild<T> = T extends { child?: any } ? Omit<T, 'child'> : T;
export type WithoutChildren<T> = T extends { children?: any }
    ? Omit<T, 'children'>
    : T;
export type WithoutChildrenOrChild<T> = WithoutChildren<WithoutChild<T>>;
