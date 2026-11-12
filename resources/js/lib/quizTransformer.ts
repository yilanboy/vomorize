import type {
    QuizOptionPayload,
    QuizQuestionPayload,
    SessionStep,
} from '@/types/quiz';

/**
 * Convert an audio_to_definition question to either word_to_definition or definition_to_word.
 */
export function convertAudioQuestion(
    step: SessionStep,
    targetType: 'word_to_definition' | 'definition_to_word',
): SessionStep {
    if (!step.quiz) {
        return step;
    }

    const isDefinitionToWord = targetType === 'definition_to_word';

    const newOptions: QuizOptionPayload[] = step.quiz.options.map((opt) => ({
        ...opt,
        text: isDefinitionToWord ? opt.word : opt.definition,
    }));

    const newStem = isDefinitionToWord
        ? step.vocabulary.definition
        : step.vocabulary.word;

    const newQuiz: QuizQuestionPayload = {
        ...step.quiz,
        quiz_type: targetType,
        stem: newStem,
        options: newOptions,
    };

    return {
        ...step,
        id: `${step.id}-converted`,
        quiz: newQuiz,
    };
}

/**
 * Transform quiz steps when listening is disabled starting from startIndex.
 *
 * For each audio_to_definition question at or after startIndex:
 * - If the same vocabulary is tested elsewhere in the session (e.g. Stage 1 / Stage 2~6 where each word has 2 questions):
 *   - Find the other question for this vocabulary.
 *   - If the other question is 'word_to_definition', this question becomes 'definition_to_word'.
 *   - If the other question is 'definition_to_word', this question becomes 'word_to_definition'.
 * - If the vocabulary only has this one question (e.g. Custom Quiz):
 *   - Randomly choose between 'word_to_definition' and 'definition_to_word' (using randomFn).
 *
 * Steps before startIndex are preserved as-is.
 */
export function disableListeningInSteps(
    steps: SessionStep[],
    startIndex: number = 0,
    randomFn: () => number = Math.random,
): SessionStep[] {
    const result: SessionStep[] = [...steps];

    for (let i = startIndex; i < result.length; i++) {
        const step = result[i];
        if (
            step.kind !== 'quiz' ||
            !step.quiz ||
            step.quiz.quiz_type !== 'audio_to_definition'
        ) {
            continue;
        }

        const vocabId = step.vocabulary.id;

        // Look for the other quiz question for this same vocabulary in the session steps
        const otherStep = steps.find(
            (s, sIndex) =>
                sIndex !== i &&
                s.kind === 'quiz' &&
                s.quiz !== null &&
                s.vocabulary.id === vocabId,
        );

        let targetType: 'word_to_definition' | 'definition_to_word';

        if (otherStep && otherStep.quiz) {
            // Guarantee no duplication: pick the opposite of the other question's type
            if (otherStep.quiz.quiz_type === 'word_to_definition') {
                targetType = 'definition_to_word';
            } else if (otherStep.quiz.quiz_type === 'definition_to_word') {
                targetType = 'word_to_definition';
            } else {
                // In case otherStep was also audio_to_definition (safe fallback)
                targetType =
                    randomFn() < 0.5
                        ? 'word_to_definition'
                        : 'definition_to_word';
            }
        } else {
            // Single-question vocabulary (e.g. Custom Quiz) -> 50/50 random choice
            targetType =
                randomFn() < 0.5 ? 'word_to_definition' : 'definition_to_word';
        }

        result[i] = convertAudioQuestion(step, targetType);
    }

    return result;
}
