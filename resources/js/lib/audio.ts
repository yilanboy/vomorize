const CACHE_TTL = 30 * 60 * 1000;

export type AudioState = 'idle' | 'loading' | 'playing' | 'error';

interface AudioEntry {
    audio: HTMLAudioElement;
    state: AudioState;
    loadedAt: number;
    loadingPromise: Promise<HTMLAudioElement | null> | null;
    listeners: Set<(state: AudioState) => void>;
}

interface ActivePlayback {
    url: string;
    audio: HTMLAudioElement;
    finish: (success: boolean) => void;
}

const audioCache = new Map<string, AudioEntry>();
let activePlayback: ActivePlayback | null = null;

function notify(entry: AudioEntry): void {
    entry.listeners.forEach((listener) => listener(entry.state));
}

/**
 * An entry that has never finished loading is still perfectly usable — it is simply waiting
 * for its first play. Only an errored or expired entry has to be rebuilt.
 */
function isReusable(entry: AudioEntry): boolean {
    if (entry.loadingPromise || entry.state === 'playing') {
        return true;
    }

    if (entry.state === 'error') {
        return false;
    }

    return entry.loadedAt === 0 || Date.now() - entry.loadedAt < CACHE_TTL;
}

/**
 * Detaches the media element from its source. Assigning an empty string would resolve
 * against the document URL and make the browser fetch the page itself as media.
 */
function release(audio: HTMLAudioElement): void {
    audio.pause();
    audio.removeAttribute('src');
    audio.load();
}

function getEntry(url: string): AudioEntry | null {
    if (typeof window === 'undefined' || !url) {
        return null;
    }

    const existing = audioCache.get(url);

    if (existing && isReusable(existing)) {
        return existing;
    }

    const entry: AudioEntry = {
        audio: new Audio(url),
        state: 'idle',
        loadedAt: 0,
        loadingPromise: null,
        // The very same set, so subscribers of the replaced entry keep hearing about state
        // changes and the unsubscribe callbacks they were handed still remove them.
        listeners: existing ? existing.listeners : new Set(),
    };
    entry.audio.preload = 'auto';
    audioCache.set(url, entry);

    if (existing) {
        release(existing.audio);
    }

    return entry;
}

function loadAudio(url: string): Promise<HTMLAudioElement | null> {
    const entry = getEntry(url);

    if (!entry) {
        return Promise.resolve(null);
    }

    if (entry.state === 'playing' || entry.loadedAt > 0) {
        return Promise.resolve(entry.audio);
    }

    if (entry.loadingPromise) {
        return entry.loadingPromise;
    }

    entry.state = 'loading';
    notify(entry);
    entry.loadingPromise = new Promise((resolve) => {
        let settled = false;

        const finish = (audio: HTMLAudioElement | null): void => {
            if (settled) {
                return;
            }

            settled = true;
            entry.audio.removeEventListener('loadeddata', onLoaded);
            entry.audio.removeEventListener('canplaythrough', onLoaded);
            entry.audio.removeEventListener('error', onError);
            entry.loadingPromise = null;

            if (audio) {
                entry.loadedAt = Date.now();
                entry.state = 'idle';
            } else {
                entry.state = 'error';
            }

            notify(entry);
            resolve(audio);
        };

        const onLoaded = (): void => finish(entry.audio);
        const onError = (): void => finish(null);

        entry.audio.addEventListener('loadeddata', onLoaded, { once: true });
        entry.audio.addEventListener('canplaythrough', onLoaded, { once: true });
        entry.audio.addEventListener('error', onError, { once: true });
        entry.audio.load();
    });

    return entry.loadingPromise;
}

function stopActivePlayback(): void {
    if (!activePlayback) {
        return;
    }

    const playback = activePlayback;
    activePlayback = null;
    playback.audio.pause();
    playback.audio.currentTime = 0;
    const entry = audioCache.get(playback.url);

    if (entry) {
        entry.state = 'idle';
        notify(entry);
    }

    playback.finish(true);
}

export function getAudioState(url: string): AudioState {
    return audioCache.get(url)?.state || 'idle';
}

export function subscribeAudio(url: string, listener: (state: AudioState) => void): () => void {
    const entry = getEntry(url);

    if (!entry) {
        return () => {};
    }

    entry.listeners.add(listener);
    listener(entry.state);

    return () => entry.listeners.delete(listener);
}

export function preloadAudio(urls: string[]): void {
    if (typeof window === 'undefined') {
        return;
    }

    [...new Set(urls.filter(Boolean))].forEach((url) => {
        void loadAudio(url);
    });
}

export async function playAudio(url: string): Promise<boolean> {
    if (typeof window === 'undefined' || !url) {
        return false;
    }

    const audio = await loadAudio(url);

    if (!audio) {
        return false;
    }

    stopActivePlayback();
    const entry = audioCache.get(url);

    if (!entry) {
        return false;
    }

    entry.state = 'playing';
    notify(entry);

    return new Promise((resolve) => {
        const finish = (success: boolean): void => {
            audio.removeEventListener('ended', onEnded);
            audio.removeEventListener('error', onError);

            if (activePlayback?.audio === audio) {
                activePlayback = null;
                entry.state = success ? 'idle' : 'error';
                notify(entry);
            }

            resolve(success);
        };
        const onEnded = (): void => finish(true);
        const onError = (): void => finish(false);

        activePlayback = { url, audio, finish };
        audio.addEventListener('ended', onEnded, { once: true });
        audio.addEventListener('error', onError, { once: true });
        audio.currentTime = 0;
        audio.play().catch(() => finish(false));
    });
}

export function playCorrectSound(): void {
    playAudio('https://assets.vomorize.com/quiz/correct.mp3').catch(() => {});
}

export function playWrongSound(): void {
    playAudio('https://assets.vomorize.com/quiz/wrong.mp3').catch(() => {});
}
