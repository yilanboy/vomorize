/**
 * Pure Web Audio API Audio Player & Preloader.
 * Preloads and decodes audio files from Cloudflare R2 into RAM for instantaneous (0ms) playback.
 */

let audioContext: AudioContext | null = null;
let activeSource: AudioBufferSourceNode | null = null;
let masterGain: GainNode | null = null;
let playGeneration = 0;
const bufferCache = new Map<string, AudioBuffer>();
const inFlightFetches = new Map<string, Promise<AudioBuffer | null>>();

let currentPlayingId = $state<string | null>(null);

// Default volume: 0.5 = 50% volume, 1 = original volume
const DEFAULT_VOLUME = 0.5;
let volume = $state(DEFAULT_VOLUME);

function getAudioContext(): AudioContext | null {
    if (typeof window === 'undefined' || !window.AudioContext) {
        return null;
    }

    if (!audioContext) {
        audioContext = new window.AudioContext();
        masterGain = audioContext.createGain();
        masterGain.gain.value = volume;
        masterGain.connect(audioContext.destination);
    }

    return audioContext;
}

// Automatically resume suspended AudioContext on first user interaction
if (typeof window !== 'undefined') {
    const unlockAudio = () => {
        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume().catch(() => {});
        }
    };

    window.addEventListener('pointerdown', unlockAudio, {
        passive: true,
        capture: true,
    });
    window.addEventListener('keydown', unlockAudio, {
        passive: true,
        capture: true,
    });
}

async function ensureResumed(context: AudioContext): Promise<void> {
    if (context.state === 'suspended') {
        try {
            await context.resume();
        } catch {
            // Ignore resume failure
        }
    }
}

async function fetchAndDecode(url: string): Promise<AudioBuffer | null> {
    const cached = bufferCache.get(url);
    if (cached) {
        return cached;
    }

    const inFlight = inFlightFetches.get(url);
    if (inFlight) {
        return inFlight;
    }

    const fetchPromise = (async (): Promise<AudioBuffer | null> => {
        try {
            const response = await fetch(url, { mode: 'cors' });
            if (!response.ok) {
                return null;
            }

            const arrayBuffer = await response.arrayBuffer();
            const context = getAudioContext();
            if (!context) {
                return null;
            }

            const audioBuffer = await context.decodeAudioData(arrayBuffer);
            bufferCache.set(url, audioBuffer);

            return audioBuffer;
        } catch {
            return null;
        } finally {
            inFlightFetches.delete(url);
        }
    })();

    inFlightFetches.set(url, fetchPromise);

    return fetchPromise;
}

export const audioPlayer = {
    get currentPlayingId(): string | null {
        return currentPlayingId;
    },

    isPlaying(id: string): boolean {
        return currentPlayingId === id;
    },

    get volume(): number {
        return volume;
    },

    setVolume(value: number): void {
        if (!Number.isFinite(value)) {
            return;
        }

        volume = Math.min(1, Math.max(0, value));

        if (masterGain && audioContext) {
            masterGain.gain.setTargetAtTime(
                volume,
                audioContext.currentTime,
                0.01,
            );
        }
    },

    /**
     * Preload and decode a list of audio URLs into memory.
     */
    async preload(urls: string[]): Promise<void> {
        if (typeof window === 'undefined' || urls.length === 0) {
            return;
        }

        getAudioContext();

        // Concurrently fetch and decode all target audio files
        await Promise.allSettled(urls.map((url) => fetchAndDecode(url)));
    },

    /**
     * Play an audio URL with exclusive single-playback semantics.
     * Stops any currently playing audio immediately.
     */
    async play(url: string, id: string): Promise<void> {
        this.stop();
        const generation = ++playGeneration;

        const context = getAudioContext();
        if (!context) {
            return;
        }

        await ensureResumed(context);
        if (generation !== playGeneration) {
            return;
        }

        const buffer = bufferCache.get(url) || (await fetchAndDecode(url));
        if (!buffer || generation !== playGeneration) {
            return;
        }

        const source = context.createBufferSource();
        source.buffer = buffer;
        source.connect(masterGain ?? context.destination);

        currentPlayingId = id;
        activeSource = source;

        source.onended = () => {
            if (currentPlayingId === id) {
                currentPlayingId = null;
            }
            if (activeSource === source) {
                activeSource = null;
            }
        };

        try {
            source.start(0);
        } catch {
            if (currentPlayingId === id) {
                currentPlayingId = null;
            }
            activeSource = null;
        }
    },

    /**
     * Immediately stop active playback.
     */
    stop(): void {
        playGeneration += 1;
        if (activeSource) {
            try {
                activeSource.stop();
            } catch {
                // Ignore if already stopped
            }
            try {
                activeSource.disconnect();
            } catch {
                // Ignore disconnect errors
            }
            activeSource = null;
        }
        currentPlayingId = null;
    },
};
