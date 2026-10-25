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
    part_of_speech: string;
    word: string;
    definition: string;
}

export type QuizType =
    | 'word_to_definition'
    | 'definition_to_word'
    | 'audio_to_definition';

export interface QuizQuestionPayload {
    quiz_type: QuizType;
    stem: string;
    audio_url: string;
    options: QuizOptionPayload[];
    correct_option_id: number;
}

export interface SessionStep {
    id: string;
    step_index: number;
    kind: 'intro' | 'quiz';
    vocabulary: VocabularyPayload;
    quiz: QuizQuestionPayload | null;
}

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
