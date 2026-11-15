<?php

declare(strict_types=1);

test('all 7000 vocabularies have valid single-blank sentence cloze data with same-group distractors', function () {
    $vocabById = [];
    $groupIdsByVocabId = [];
    $clozeEntries = [];

    for ($levelId = 1; $levelId <= 7; $levelId++) {
        $vocabFile = dirname(__DIR__, 2)."/database/data/zh_TW/level_{$levelId}_vocabulary.php";
        $clozeFile = dirname(__DIR__, 2)."/database/data/zh_TW/level_{$levelId}_sentence_cloze.php";

        expect(file_exists($vocabFile))->toBeTrue()
            ->and(file_exists($clozeFile))->toBeTrue();

        $vocabGroups = require $vocabFile;
        $clozeGroups = require $clozeFile;

        expect(count($clozeGroups))->toBe(count($vocabGroups));

        foreach ($vocabGroups as $groupKey => $items) {
            foreach ($items as $item) {
                $id = $item['id'];
                $vocabById[$id] = $item;
                $groupIdsByVocabId[$id] = "level_{$levelId}_{$groupKey}";
            }
        }

        foreach ($clozeGroups as $items) {
            foreach ($items as $item) {
                $clozeEntries[$item['vocabulary_id']] = $item;
            }
        }
    }

    expect(count($vocabById))->toBe(7000)
        ->and(count($clozeEntries))->toBe(7000);

    foreach ($vocabById as $vocabId => $vocab) {
        expect($clozeEntries)->toHaveKey($vocabId);
        $cloze = $clozeEntries[$vocabId];

        expect(substr_count($cloze['sentence'], '{{blank}}'))->toBe(1)
            ->and(trim($cloze['blank_word']))->not->toBeEmpty()
            ->and(trim($cloze['translation']))->not->toBeEmpty()
            ->and(count($cloze['distractors']))->toBeGreaterThanOrEqual(3);

        $seenIds = [$vocabId];
        $seenWords = [strtolower($cloze['blank_word'])];

        foreach ($cloze['distractors'] as $distractor) {
            $dId = $distractor['vocabulary_id'];
            $dWord = trim($distractor['word']);

            expect($vocabById)->toHaveKey($dId)
                ->and($groupIdsByVocabId[$dId])->toBe($groupIdsByVocabId[$vocabId])
                ->and($seenIds)->not->toContain($dId)
                ->and($dWord)->not->toBeEmpty()
                ->and($seenWords)->not->toContain(strtolower($dWord));

            $seenIds[] = $dId;
            $seenWords[] = strtolower($dWord);
        }
    }
});
