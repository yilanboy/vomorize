import { describe, expect, it } from 'vitest';
import {
    convertAudioQuestion,
    disableListeningInSteps,
} from '@/lib/quizTransformer';
import type { SessionStep } from '@/types/quiz';

function makeMockStep(
    id: string,
    vocabId: number,
    word: string,
    definition: string,
    quizType:
        | 'word_to_definition'
        | 'definition_to_word'
        | 'audio_to_definition',
): SessionStep {
    const isDefinitionToWord = quizType === 'definition_to_word';
    return {
        id,
        step_index: 1,
        kind: 'quiz',
        vocabulary: {
            id: vocabId,
            word,
            part_of_speech: 'n.',
            pronunciation: '/test/',
            definition,
            example_sentence: 'This is a test.',
            example_translation: '這是一個測試。',
            word_audio_url: 'https://assets.vomorize.com/word.mp3',
            sentence_audio_url: 'https://assets.vomorize.com/sentence.mp3',
        },
        quiz: {
            quiz_type: quizType,
            stem:
                quizType === 'word_to_definition'
                    ? word
                    : quizType === 'definition_to_word'
                      ? definition
                      : '',
            audio_url: 'https://assets.vomorize.com/word.mp3',
            correct_option_id: vocabId,
            options: [
                {
                    id: vocabId,
                    text: isDefinitionToWord ? word : definition,
                    part_of_speech: 'n.',
                    word,
                    definition,
                },
                {
                    id: 999,
                    text: isDefinitionToWord ? 'distractor' : '干擾項',
                    part_of_speech: 'n.',
                    word: 'distractor',
                    definition: '干擾項',
                },
            ],
        },
    };
}

describe('quizTransformer', () => {
    describe('convertAudioQuestion', () => {
        it('converts audio_to_definition to word_to_definition correctly', () => {
            const step = makeMockStep(
                'step-1',
                10,
                'apple',
                '蘋果',
                'audio_to_definition',
            );
            const converted = convertAudioQuestion(step, 'word_to_definition');

            expect(converted.quiz?.quiz_type).toBe('word_to_definition');
            expect(converted.quiz?.stem).toBe('apple');
            expect(converted.quiz?.options[0].text).toBe('蘋果');
            expect(converted.quiz?.options[1].text).toBe('干擾項');
            expect(converted.id).toBe('step-1-converted');
        });

        it('converts audio_to_definition to definition_to_word correctly', () => {
            const step = makeMockStep(
                'step-1',
                10,
                'apple',
                '蘋果',
                'audio_to_definition',
            );
            const converted = convertAudioQuestion(step, 'definition_to_word');

            expect(converted.quiz?.quiz_type).toBe('definition_to_word');
            expect(converted.quiz?.stem).toBe('蘋果');
            expect(converted.quiz?.options[0].text).toBe('apple');
            expect(converted.quiz?.options[1].text).toBe('distractor');
            expect(converted.id).toBe('step-1-converted');
        });
    });

    describe('disableListeningInSteps', () => {
        it('guarantees no duplicate question types for 2-question vocabulary', () => {
            // Vocab 1: has audio_to_definition and word_to_definition
            // Vocab 2: has audio_to_definition and definition_to_word
            // Vocab 3: has word_to_definition and definition_to_word
            const steps: SessionStep[] = [
                makeMockStep('s1', 1, 'apple', '蘋果', 'audio_to_definition'),
                makeMockStep('s2', 2, 'banana', '香蕉', 'audio_to_definition'),
                makeMockStep('s3', 3, 'cat', '貓', 'word_to_definition'),
                makeMockStep('s4', 1, 'apple', '蘋果', 'word_to_definition'),
                makeMockStep('s5', 2, 'banana', '香蕉', 'definition_to_word'),
                makeMockStep('s6', 3, 'cat', '貓', 'definition_to_word'),
            ];

            const transformed = disableListeningInSteps(steps, 0);

            // Vocab 1's audio question must become definition_to_word because s4 is word_to_definition
            expect(transformed[0].quiz?.quiz_type).toBe('definition_to_word');
            expect(transformed[3].quiz?.quiz_type).toBe('word_to_definition');

            // Vocab 2's audio question must become word_to_definition because s5 is definition_to_word
            expect(transformed[1].quiz?.quiz_type).toBe('word_to_definition');
            expect(transformed[4].quiz?.quiz_type).toBe('definition_to_word');

            // Vocab 3 has no audio, neither should change
            expect(transformed[2].quiz?.quiz_type).toBe('word_to_definition');
            expect(transformed[5].quiz?.quiz_type).toBe('definition_to_word');

            // Verify no audio_to_definition remains anywhere
            const hasAudio = transformed.some(
                (s) => s.quiz?.quiz_type === 'audio_to_definition',
            );
            expect(hasAudio).toBe(false);
        });

        it('does not touch questions before startIndex', () => {
            const steps: SessionStep[] = [
                makeMockStep('s1', 1, 'apple', '蘋果', 'audio_to_definition'),
                makeMockStep('s2', 2, 'banana', '香蕉', 'audio_to_definition'),
            ];

            // User disables listening at index 1 (after step 0 was already answered)
            const transformed = disableListeningInSteps(steps, 1);

            // s1 remains audio_to_definition
            expect(transformed[0].quiz?.quiz_type).toBe('audio_to_definition');
            // s2 is transformed
            expect(transformed[1].quiz?.quiz_type).not.toBe(
                'audio_to_definition',
            );
        });

        it('randomly selects non-listening type for single-question vocabulary (Custom Quiz)', () => {
            const step = makeMockStep(
                'custom-1',
                100,
                'zebra',
                '斑馬',
                'audio_to_definition',
            );

            // Test random branch < 0.5 -> word_to_definition
            const transformed1 = disableListeningInSteps([step], 0, () => 0.2);
            expect(transformed1[0].quiz?.quiz_type).toBe('word_to_definition');

            // Test random branch >= 0.5 -> definition_to_word
            const transformed2 = disableListeningInSteps([step], 0, () => 0.8);
            expect(transformed2[0].quiz?.quiz_type).toBe('definition_to_word');
        });

        it('skips intro steps without quiz', () => {
            const introStep: SessionStep = {
                id: 'intro-1',
                step_index: 1,
                kind: 'intro',
                vocabulary: {
                    id: 1,
                    word: 'apple',
                    part_of_speech: 'n.',
                    pronunciation: '/test/',
                    definition: '蘋果',
                    example_sentence: '',
                    example_translation: '',
                    word_audio_url: '',
                    sentence_audio_url: '',
                },
                quiz: null,
            };

            const transformed = disableListeningInSteps([introStep], 0);
            expect(transformed[0].quiz).toBeNull();
            expect(transformed[0].kind).toBe('intro');
        });
    });
});
