export interface VocabularyPayload {
    id: number;
    word: string;
    part_of_speech: string;
    pronunciation: string;
    definition: string;
    example_sentence: string;
    example_translation: string;
    word_audio_url: string;
    sentence_audio_url: string;
}

export interface QuizOptionPayload {
    id: number;
    text: string;
    is_correct: boolean;
    part_of_speech: string;
    counterpart: string;
}

export type QuizType =
    | 'word_to_definition'
    | 'definition_to_word'
    | 'audio_to_definition';

export interface QuizQuestionPayload {
    id: number;
    type: QuizType;
    prompt: string;
    target_word: VocabularyPayload;
    options: QuizOptionPayload[];
}

export interface IntroStep {
    step_index: number;
    kind: 'intro';
    data: VocabularyPayload;
}

export interface QuizStep {
    step_index: number;
    kind: 'quiz';
    data: QuizQuestionPayload;
}

export type SessionStep = IntroStep | QuizStep;

export interface Stage1SessionData {
    steps: SessionStep[];
    correct_audio_url: string;
    wrong_audio_url: string;
}

export interface MistakeReviewItem {
    id: number;
    word: string;
    part_of_speech: string;
    pronunciation: string;
    definition: string;
    word_audio_url: string;
    sentence_audio_url: string;
    example_sentence: string;
    example_translation: string;
}
