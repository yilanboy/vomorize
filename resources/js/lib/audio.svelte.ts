import { SvelteMap } from 'svelte/reactivity';

/**
 * Audio playback state:
 * - `idle`: Idle or playback stopped.
 * - `loading`: Loading audio / preparing to play.
 * - `playing`: Currently playing.
 * - `error`: Failed to load or play audio.
 */
export type AudioState = 'idle' | 'loading' | 'playing' | 'error';

/**
 * Cached audio entry structure (reactive via Svelte 5 `$state`).
 */
export interface AudioEntry {
    /** The HTMLAudioElement instance. */
    audio: HTMLAudioElement;
    /** Current playback state. */
    state: AudioState;
    /** Resolver callback to settle the active playback Promise. */
    resolve?: (success: boolean) => void;
}

/**
 * Global reactive audio state registry.
 * - `cache`: Reactive map of URL keys to AudioEntry objects.
 * - `active`: The audio URL of the currently active playback.
 */
export const audio: {
    cache: SvelteMap<string, AudioEntry>;
    active: string;
} = $state({
    cache: new SvelteMap<string, AudioEntry>(),
    active: '',
});

/**
 * Returns the playback state for a given audio URL (defaults to 'idle').
 *
 * @param url The audio file URL.
 * @returns The current audio state.
 */
export function getAudioState(url: string): AudioState {
    return audio.cache.get(url)?.state ?? 'idle';
}

/**
 * Gets or creates the cached AudioEntry for the given URL.
 *
 * @param url The audio file URL.
 * @returns The AudioEntry instance, or null in SSR or if URL is empty.
 */
export function getEntry(url: string): AudioEntry | null {
    if (typeof window === 'undefined' || !url) {
        return null;
    }

    const existing = audio.cache.get(url);

    if (existing) {
        return existing;
    }

    const audioElement = new Audio();
    audioElement.preload = 'metadata';
    audioElement.src = url;

    const entry: AudioEntry = $state({
        audio: audioElement,
        state: 'idle',
    });

    const onPlaying = (): void => {
        if (audio.active === url) {
            entry.state = 'playing';
        }
    };

    const onEnded = (): void => {
        if (audio.active === url) {
            entry.state = 'idle';
            audio.active = '';
            const resolve = entry.resolve;
            entry.resolve = undefined;
            resolve?.(true);
        }
    };

    const onError = (): void => {
        if (audio.active === url) {
            entry.state = 'error';
            audio.active = '';
            const resolve = entry.resolve;
            entry.resolve = undefined;
            resolve?.(false);
        }
    };

    audioElement.addEventListener('playing', onPlaying);
    audioElement.addEventListener('ended', onEnded);
    audioElement.addEventListener('error', onError);

    audio.cache.set(url, entry);

    return entry;
}

/**
 * Stops the currently playing audio (if any).
 * Resets playback progress to 0, sets state to 'idle', and resolves its promise.
 */
export function stopActivePlayback(): void {
    if (!audio.active) {
        return;
    }

    const url = audio.active;
    audio.active = '';
    const playback = audio.cache.get(url);

    if (!playback) {
        return;
    }

    playback.audio.pause();
    playback.audio.currentTime = 0;
    playback.state = 'idle';

    if (playback.resolve) {
        const resolve = playback.resolve;
        playback.resolve = undefined;
        resolve(true);
    }
}

/**
 * Plays the audio at the specified URL.
 * If another audio is currently playing, it will be stopped first to guarantee exclusive playback.
 *
 * @param url The audio file URL.
 * @returns Promise that resolves to true on successful playback, or false on error.
 */
export async function playAudio(url: string): Promise<boolean> {
    if (typeof window === 'undefined' || !url) {
        return false;
    }

    const entry = getEntry(url);

    if (!entry) {
        return false;
    }

    stopActivePlayback();
    audio.active = url;
    entry.state = 'loading';

    return new Promise((resolve) => {
        entry.resolve = resolve;

        const audioElement = entry.audio;
        audioElement.currentTime = 0;
        audioElement.play().catch(() => {
            if (audio.active === url) {
                entry.state = 'error';
                audio.active = '';
                const cb = entry.resolve;
                entry.resolve = undefined;
                cb?.(false);
            }
        });
    });
}

/**
 * Plays the quiz correct sound effect.
 */
export function playCorrectSound(): void {
    playAudio('https://assets.vomorize.com/quiz/correct.mp3').catch(() => {});
}

/**
 * Plays the quiz wrong sound effect.
 */
export function playWrongSound(): void {
    playAudio('https://assets.vomorize.com/quiz/wrong.mp3').catch(() => {});
}
