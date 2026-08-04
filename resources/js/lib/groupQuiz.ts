import { currentLocale, DEFAULT_LOCALE } from '@/lib/locale.svelte';

export type QuizType = 'word_to_translation' | 'translation_to_word' | 'audio_to_translation';

/**
 * Every way a word can be asked about. The review rotation indexes into this, so the order is part
 * of its behaviour and not incidental.
 */
const QUIZ_TYPES: QuizType[] = [
    'word_to_translation',
    'translation_to_word',
    'audio_to_translation',
];

export interface QuizTranslation {
    definition: string;
}

export interface VocabularyTranslation extends QuizTranslation {
    example_translation: string;
}

/**
 * Everything a question renders, and nothing more: the word, its definition in each locale, and the
 * clip of the word being spoken. A question asks with one of those three and offers the others as
 * options; it never shows a part of speech, a pronunciation, or an example sentence.
 *
 * Naming that boundary lets a surface serve questions without carrying the introduction card's
 * fields — the practice quiz draws from thousands of words at a time, where the difference between
 * the two shapes is most of the payload.
 */
export interface QuizVocabulary {
    id: number;
    word: string;
    translations: Record<string, QuizTranslation>;
    audio_url: string;
}

/**
 * A whole vocabulary record, as the introduction cards need it. Every question-facing field is
 * inherited, so anywhere a question is built accepts one of these too.
 */
export interface VocabularyItem extends QuizVocabulary {
    part_of_speech: string;
    pronunciation: string;
    example_sentence: string;
    translations: Record<string, VocabularyTranslation>;
    sentence_audio_url: string;
}

/**
 * A question names the vocabularies its options came from rather than their text.
 *
 * Display text is the one thing changing language changes, so it cannot also be what identifies
 * an option, what the learner's selection is compared against, or what the option list is keyed
 * by. Holding references instead lets the current attempt retranslate in place — no rebuild, so
 * no reshuffle and no reset.
 *
 * There is deliberately no correct-answer field: the correct option is always the question's own
 * vocabulary, because the distractors are by construction other vocabularies.
 */
export interface QuizQuestion {
    vocabulary: QuizVocabulary;
    type: QuizType;
    options: QuizVocabulary[];
}

function shuffle<T>(items: T[]): T[] {
    return [...items].sort(() => Math.random() - 0.5);
}

/**
 * Applies the same fallback chain the server used to apply: the requested locale, then zh_TW.
 *
 * Generic over the translation shape so one fallback chain serves both: a question-facing record
 * yields a definition, a full record yields an example translation as well.
 */
function translationFor<T extends QuizTranslation>(vocabulary: {
    translations: Record<string, T>;
}): T | undefined {
    const translations = vocabulary.translations || {};

    return translations[currentLocale()] || translations[DEFAULT_LOCALE];
}

export function definitionOf(vocabulary: QuizVocabulary): string {
    return translationFor(vocabulary)?.definition || '';
}

export function exampleTranslationOf(vocabulary: VocabularyItem): string {
    return translationFor(vocabulary)?.example_translation || '';
}

/**
 * What a given vocabulary reads as when offered as an option for a question of this type.
 *
 * Resolved at render time rather than baked in at build time, so it follows the current locale.
 * One type asks for the word, which no locale changes; the other two ask for the definition.
 */
export function answerFor(vocabulary: QuizVocabulary, type: QuizType): string {
    return type === 'translation_to_word' ? vocabulary.word : definitionOf(vocabulary);
}

export function buildQuestion(
    vocabulary: QuizVocabulary,
    vocabularies: QuizVocabulary[],
    type: QuizType,
): QuizQuestion {
    const distractors = shuffle(
        vocabularies.filter((candidate) => candidate.id !== vocabulary.id),
    ).slice(0, 2);

    return {
        vocabulary,
        type,
        options: shuffle([vocabulary, ...distractors]),
    };
}

export function buildIntroductionQuestions(vocabularies: QuizVocabulary[]): QuizQuestion[] {
    return vocabularies.map((vocabulary) =>
        buildQuestion(vocabulary, vocabularies, 'word_to_translation'),
    );
}

export function buildReviewQuestions(vocabularies: QuizVocabulary[]): QuizQuestion[] {
    const vocabularyPairs = vocabularies.map((vocabulary, index) => {
        const firstType = QUIZ_TYPES[index % QUIZ_TYPES.length];
        const secondType = QUIZ_TYPES[(index + 1) % QUIZ_TYPES.length];

        return {
            vocabulary,
            questions: [
                buildQuestion(vocabulary, vocabularies, firstType),
                buildQuestion(vocabulary, vocabularies, secondType),
            ],
        };
    });
    const shuffledPairs = shuffle(vocabularyPairs);

    return [0, 1].flatMap((round) => shuffledPairs.map((pair) => pair.questions[round]));
}

/**
 * One question per word, each word asked once, with its type rolled independently.
 *
 * Coverage is the point of practice, so a word appears once rather than twice: being tested twice
 * is what makes a group review teach, and what would halve how much of a learned vocabulary a
 * session of a given length can reach. The type is rolled per word rather than rotated, which lets
 * a short session skew toward one format — accepted for a simpler rule.
 *
 * Distractors are taken alongside the words being tested rather than left to the caller to merge,
 * because a set drawn from thousands would otherwise fill every option slot from its own answers
 * and be solvable by elimination.
 */
export function buildPracticeQuestions(
    targets: QuizVocabulary[],
    distractors: QuizVocabulary[] = [],
): QuizQuestion[] {
    const optionPool = [...targets, ...distractors];

    return targets.map((vocabulary) =>
        buildQuestion(
            vocabulary,
            optionPool,
            QUIZ_TYPES[Math.floor(Math.random() * QUIZ_TYPES.length)],
        ),
    );
}
