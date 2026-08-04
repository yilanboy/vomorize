<?php

use App\Ai\Agents\VocabularyTranslatorAgent;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->testLevelPath = database_path('data/vocabulary/level_99.php');

    $dummyData = [
        'group_1' => [
            [
                'word' => 'test_word_1',
                'part_of_speech' => 'n.',
                'pronunciation' => '/test/',
                'example_sentence' => 'This is a test sentence.',
                'translations' => [
                    'zh_TW' => [
                        'definition' => '測試詞',
                        'example_translation' => '這是一個測試句子。',
                    ],
                ],
            ],
        ],
    ];

    $content = "<?php\n\nreturn ".var_export($dummyData, true).";\n";
    file_put_contents($this->testLevelPath, $content);
});

afterEach(function () {
    if (file_exists($this->testLevelPath)) {
        unlink($this->testLevelPath);
    }
});

it('translates pending vocabulary items and updates level files', function () {
    VocabularyTranslatorAgent::fake(function ($prompt) {
        return [
            'translations' => [
                [
                    'word' => 'test_word_1',
                    'zh_CN' => [
                        'definition' => '测试词',
                        'example_translation' => '这是一个测试句子。',
                    ],
                    'ja' => [
                        'definition' => 'テスト単語',
                        'example_translation' => 'これはテスト文です。',
                    ],
                ],
            ],
        ];
    });

    $this->artisan('vocabulary:translate', [
        '--level' => '99',
        '--model' => 'gemini-3.6-flash',
    ])
        ->assertSuccessful();

    VocabularyTranslatorAgent::assertPrompted(function (AgentPrompt $prompt) {
        return str_contains($prompt->prompt, 'test_word_1');
    });

    $updatedData = require $this->testLevelPath;

    expect($updatedData['group_1'][0]['translations'])->toHaveKeys(['zh_TW', 'zh_CN', 'ja'])
        ->and($updatedData['group_1'][0]['translations']['zh_CN']['definition'])->toBe('测试词')
        ->and($updatedData['group_1'][0]['translations']['ja']['definition'])->toBe('テスト単語');
});

it('skips items that already have translations unless --force is used', function () {
    $completeData = [
        'group_1' => [
            [
                'word' => 'translated_word',
                'part_of_speech' => 'n.',
                'pronunciation' => '/test/',
                'example_sentence' => 'Translated sentence.',
                'translations' => [
                    'zh_TW' => [
                        'definition' => '測試',
                        'example_translation' => '測試句。',
                    ],
                    'zh_CN' => [
                        'definition' => '测试',
                        'example_translation' => '测试句。',
                    ],
                    'ja' => [
                        'definition' => 'テスト',
                        'example_translation' => 'テスト文。',
                    ],
                ],
            ],
        ],
    ];

    file_put_contents($this->testLevelPath, "<?php\n\nreturn ".var_export($completeData, true).";\n");

    VocabularyTranslatorAgent::fake();

    $this->artisan('vocabulary:translate', [
        '--level' => '99',
    ])->assertSuccessful();

    VocabularyTranslatorAgent::assertNeverPrompted();
});

it('translates items when --force is specified even if translations exist', function () {
    $completeData = [
        'group_1' => [
            [
                'word' => 'forced_word',
                'part_of_speech' => 'n.',
                'pronunciation' => '/test/',
                'example_sentence' => 'Forced sentence.',
                'translations' => [
                    'zh_TW' => [
                        'definition' => '測試',
                        'example_translation' => '測試句。',
                    ],
                    'zh_CN' => [
                        'definition' => '旧',
                        'example_translation' => '旧句。',
                    ],
                    'ja' => [
                        'definition' => '旧',
                        'example_translation' => '旧句。',
                    ],
                ],
            ],
        ],
    ];

    file_put_contents($this->testLevelPath, "<?php\n\nreturn ".var_export($completeData, true).";\n");

    VocabularyTranslatorAgent::fake(function () {
        return [
            'translations' => [
                [
                    'word' => 'forced_word',
                    'zh_CN' => [
                        'definition' => '新',
                        'example_translation' => '新句。',
                    ],
                    'ja' => [
                        'definition' => '新',
                        'example_translation' => '新句。',
                    ],
                ],
            ],
        ];
    });

    $this->artisan('vocabulary:translate', [
        '--level' => '99',
        '--force' => true,
    ])->assertSuccessful();

    VocabularyTranslatorAgent::assertPrompted(fn (AgentPrompt $prompt) => str_contains($prompt->prompt, 'forced_word'));

    $updatedData = require $this->testLevelPath;
    expect($updatedData['group_1'][0]['translations']['zh_CN']['definition'])->toBe('新');
});

it('respects --dry-run option and does not write changes to disk', function () {
    VocabularyTranslatorAgent::fake(function () {
        return [
            'translations' => [
                [
                    'word' => 'test_word_1',
                    'zh_CN' => [
                        'definition' => '测试',
                        'example_translation' => '测试句。',
                    ],
                    'ja' => [
                        'definition' => 'テスト',
                        'example_translation' => 'テスト文。',
                    ],
                ],
            ],
        ];
    });

    $originalContent = file_get_contents($this->testLevelPath);

    $this->artisan('vocabulary:translate', [
        '--level' => '99',
        '--dry-run' => true,
    ])->assertSuccessful();

    VocabularyTranslatorAgent::assertPrompted(fn (AgentPrompt $prompt) => str_contains($prompt->prompt, 'test_word_1'));

    expect(file_get_contents($this->testLevelPath))->toBe($originalContent);
});

it('translates specified locales when --locales option is passed', function () {
    VocabularyTranslatorAgent::fake(function ($prompt) {
        return [
            'translations' => [
                [
                    'word' => 'test_word_1',
                    'results' => [
                        [
                            'locale' => 'zh_TW',
                            'definition' => '新測試詞',
                            'example_translation' => '這是一個新測試句子。',
                        ],
                    ],
                ],
            ],
        ];
    });

    $this->artisan('vocabulary:translate', [
        '--level' => '99',
        '--locales' => 'zh_TW',
        '--force' => true,
    ])->assertSuccessful();

    VocabularyTranslatorAgent::assertPrompted(function (AgentPrompt $prompt) {
        return str_contains($prompt->prompt, 'zh_TW') && str_contains($prompt->prompt, 'test_word_1');
    });

    $updatedData = require $this->testLevelPath;

    expect($updatedData['group_1'][0]['translations']['zh_TW']['definition'])->toBe('新測試詞');
});

it('includes existing translations in payload to preserve meaning across locales', function () {
    VocabularyTranslatorAgent::fake(function () {
        return [
            'translations' => [
                [
                    'word' => 'test_word_1',
                    'zh_CN' => [
                        'definition' => '测试词',
                        'example_translation' => '这是一个测试句子。',
                    ],
                ],
            ],
        ];
    });

    $this->artisan('vocabulary:translate', [
        '--level' => '99',
        '--locales' => 'zh_CN',
    ])->assertSuccessful();

    VocabularyTranslatorAgent::assertPrompted(function (AgentPrompt $prompt) {
        return str_contains($prompt->prompt, 'existing_translations')
            && str_contains($prompt->prompt, '測試詞');
    });
});
