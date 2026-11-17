<?php

declare(strict_types=1);

use App\Enums\Locale;

test('all supported locales have complete non-empty level translations matching root levels', function () {
    $dataDir = dirname(__DIR__, 2).'/database/data';
    $levelsFile = "{$dataDir}/levels.php";

    expect(file_exists($levelsFile))->toBeTrue();

    /** @var list<int> $levelIds */
    $levelIds = require $levelsFile;
    expect($levelIds)->toBe([1, 2, 3, 4, 5, 6, 7]);

    foreach (Locale::values() as $locale) {
        $localeLevelsFile = "{$dataDir}/{$locale}/levels.php";
        expect(file_exists($localeLevelsFile))->toBeTrue();

        /** @var array<int, array{name: string, description: string}> $translations */
        $translations = require $localeLevelsFile;

        expect(array_keys($translations))->toBe($levelIds);

        foreach ($levelIds as $levelId) {
            expect(trim($translations[$levelId]['name']))->not->toBeEmpty()
                ->and(trim($translations[$levelId]['description']))->not->toBeEmpty();
        }
    }
});

test('all supported locales have complete non-empty vocabulary translations matching root vocabularies', function () {
    $dataDir = dirname(__DIR__, 2).'/database/data';
    $expectedCoreVocabIds = [];

    for ($levelId = 1; $levelId <= 7; $levelId++) {
        $coreFile = "{$dataDir}/level_{$levelId}_vocabulary.php";
        expect(file_exists($coreFile))->toBeTrue();

        /** @var array<string, array<int, array{id: int, word: string, part_of_speech: string, pronunciation: string, example_sentence: string}>> $groups */
        $groups = require $coreFile;
        $levelVocabIds = [];

        foreach ($groups as $items) {
            foreach ($items as $item) {
                expect(trim($item['word']))->not->toBeEmpty()
                    ->and(trim($item['part_of_speech']))->not->toBeEmpty()
                    ->and(trim($item['pronunciation']))->not->toBeEmpty()
                    ->and(trim($item['example_sentence']))->not->toBeEmpty();

                $levelVocabIds[] = $item['id'];
                $expectedCoreVocabIds[] = $item['id'];
            }
        }

        foreach (Locale::values() as $locale) {
            $translationFile = "{$dataDir}/{$locale}/level_{$levelId}_vocabulary.php";
            expect(file_exists($translationFile))->toBeTrue();

            /** @var array<int, array{definition: string, example_translation: string}> $translations */
            $translations = require $translationFile;

            expect(array_keys($translations))->toBe($levelVocabIds);

            foreach ($levelVocabIds as $vocabId) {
                expect(trim($translations[$vocabId]['definition']))->not->toBeEmpty()
                    ->and(trim($translations[$vocabId]['example_translation']))->not->toBeEmpty();
            }
        }
    }

    expect($expectedCoreVocabIds)->toHaveCount(7000);
});
