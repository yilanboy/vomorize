export type InitialsApi = {
    getInitials: (fullName?: string) => string;
};

function getInitial(name: string): string {
    return Array.from(name)[0] ?? '';
}

export function getInitials(fullName?: string): string {
    if (!fullName) {
        return '';
    }

    const names = fullName.trim().split(/\s+/u).filter(Boolean);

    if (names.length === 0) {
        return '';
    }

    if (names.length === 1) {
        return getInitial(names[0]).toUpperCase();
    }

    return `${getInitial(names[0])}${getInitial(names[names.length - 1])}`.toUpperCase();
}

export function getFirstCharacter(fullName?: string): string {
    if (!fullName) {
        return '';
    }

    const trimmed = fullName.trim();

    if (!trimmed) {
        return '';
    }

    return (Array.from(trimmed)[0] ?? '').toUpperCase();
}

export function initialsApi(): InitialsApi {
    return { getInitials };
}
