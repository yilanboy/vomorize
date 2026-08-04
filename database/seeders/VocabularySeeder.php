<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Level;
use App\Models\LevelTranslation;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Illuminate\Database\Seeder;

/**
 * @phpstan-type WordRow array{
 *     word: string,
 *     part_of_speech: string,
 *     pronunciation: string,
 *     example_sentence: string,
 *     translations: array<string, array{definition: string, example_translation: string}>
 * }
 * @phpstan-type LevelData array<string, array<int, WordRow>>
 */
class VocabularySeeder extends Seeder
{
    private const int CHUNK_SIZE = 500;

    private const string GROUP_KEY_PREFIX = 'group_';

    /**
     * @var array<int, array<string, array{name: string, description: string}>>
     */
    private const array LEVELS = [
        1 => [
            'zh_TW' => ['name' => '等級 1', 'description' => '基礎入門：核心常用 1,000 單字'],
            'zh_CN' => ['name' => '等级 1', 'description' => '基础入门：核心常用 1,000 单词'],
            'ja' => ['name' => 'レベル 1', 'description' => '初級：よく使われる必須1,000単語'],
        ],
        2 => [
            'zh_TW' => ['name' => '等級 2', 'description' => '初級進階：日常溝通必備詞彙'],
            'zh_CN' => ['name' => '等级 2', 'description' => '初级进阶：日常沟通必备词汇'],
            'ja' => ['name' => 'レベル 2', 'description' => '初中級：日常会話に必要な必須単語'],
        ],
        3 => [
            'zh_TW' => ['name' => '等級 3', 'description' => '中級應用：掌握生活與社交話題'],
            'zh_CN' => ['name' => '等级 3', 'description' => '中级应用：掌握生活与社交话题'],
            'ja' => ['name' => 'レベル 3', 'description' => '中級：日常・社会生活の話題に対応できる単語'],
        ],
        4 => [
            'zh_TW' => ['name' => '等級 4', 'description' => '中高級加強：理解複雜議題與表達'],
            'zh_CN' => ['name' => '等级 4', 'description' => '中高级加强：理解复杂议题与表达'],
            'ja' => ['name' => 'レベル 4', 'description' => '中上級：複雑なトピックを理解・表現する単語'],
        ],
        5 => [
            'zh_TW' => ['name' => '等級 5', 'description' => '高級深造：學術與專業領域詞彙'],
            'zh_CN' => ['name' => '等级 5', 'description' => '高级深造：学术与专业领域词汇'],
            'ja' => ['name' => 'レベル 5', 'description' => '上級：学術・専門分野の重要単語'],
        ],
        6 => [
            'zh_TW' => ['name' => '等級 6', 'description' => '大師精通：流暢運用高難度核心詞彙'],
            'zh_CN' => ['name' => '等级 6', 'description' => '大师精通：流畅运用高难度核心词汇'],
            'ja' => ['name' => 'レベル 6', 'description' => '最上級：高度な核心単語を自在に使いこなす'],
        ],
        7 => [
            'zh_TW' => ['name' => '等級 7', 'description' => '額外補充：進階加分詞彙'],
            'zh_CN' => ['name' => '等级 7', 'description' => '额外补充：进阶加分词汇'],
            'ja' => ['name' => 'レベル 7', 'description' => '補足：応用・スコアアップ単語'],
        ],
    ];

    public function run(): void
    {
        $levelData = $this->loadLevelData();

        $this->seedLevels();
        $this->seedGroups($levelData);
        $this->seedVocabularies($levelData);
    }

    /**
     * Load every level's group data file, keyed by level id.
     *
     * @return array<int, LevelData>
     */
    private function loadLevelData(): array
    {
        $levelData = [];

        foreach (array_keys(self::LEVELS) as $levelId) {
            $levelData[$levelId] = require database_path("data/vocabulary/level_{$levelId}.php");
        }

        return $levelData;
    }

    private function seedLevels(): void
    {
        $levelRows = [];
        $translationRows = [];

        foreach (self::LEVELS as $id => $translations) {
            $levelRows[] = [
                'id' => $id,
            ];

            foreach ($translations as $locale => $translation) {
                $translationRows[] = [
                    'level_id' => $id,
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description'],
                ];
            }
        }

        Level::upsert($levelRows, ['id'], []);
        LevelTranslation::upsert($translationRows, ['level_id', 'locale'], ['name', 'description']);
    }

    /**
     * @param  array<int, LevelData>  $levelData
     */
    private function seedGroups(array $levelData): void
    {
        $rows = [];

        foreach ($levelData as $levelId => $groups) {
            foreach (array_keys($groups) as $groupKey) {
                $rows[] = [
                    'level_id' => $levelId,
                    'sequence' => $this->sequenceFromKey($groupKey),
                ];
            }
        }

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            Group::upsert($chunk, ['level_id', 'sequence'], ['sequence']);
        }
    }

    /**
     * @param  array<int, LevelData>  $levelData
     */
    private function seedVocabularies(array $levelData): void
    {
        $groupIds = Group::query()
            ->get(['id', 'level_id', 'sequence'])
            ->keyBy(fn (Group $group) => "{$group->level_id}-{$group->sequence}")
            ->map(fn (Group $group) => $group->id);

        $vocabularyRows = [];
        $translationsByWord = [];

        foreach ($levelData as $levelId => $groups) {
            foreach ($groups as $groupKey => $words) {
                $sequence = $this->sequenceFromKey($groupKey);
                $groupId = $groupIds["{$levelId}-{$sequence}"];

                foreach ($words as $word) {
                    $vocabularyRows[] = [
                        'group_id' => $groupId,
                        'word' => $word['word'],
                        'part_of_speech' => $word['part_of_speech'],
                        'pronunciation' => $word['pronunciation'],
                        'example_sentence' => $word['example_sentence'],
                    ];

                    $translationsByWord["{$groupId}|{$word['word']}"] = $word['translations'];
                }
            }
        }

        foreach (array_chunk($vocabularyRows, self::CHUNK_SIZE) as $chunk) {
            Vocabulary::upsert($chunk, ['group_id', 'word'], ['part_of_speech', 'pronunciation', 'example_sentence']);
        }

        $translationRows = [];

        Vocabulary::query()
            ->get(['id', 'group_id', 'word'])
            ->each(function (Vocabulary $vocabulary) use ($translationsByWord, &$translationRows) {
                $translations = $translationsByWord["{$vocabulary->group_id}|{$vocabulary->word}"] ?? [];

                foreach ($translations as $locale => $translation) {
                    $translationRows[] = [
                        'vocabulary_id' => $vocabulary->id,
                        'locale' => $locale,
                        'definition' => $translation['definition'],
                        'example_translation' => $translation['example_translation'],
                    ];
                }
            });

        foreach (array_chunk($translationRows, self::CHUNK_SIZE) as $chunk) {
            VocabularyTranslation::upsert($chunk, ['vocabulary_id', 'locale'], ['definition', 'example_translation']);
        }
    }

    /**
     * Extract the numeric group sequence from a data-file key such as 'group_1'.
     */
    private function sequenceFromKey(string $groupKey): int
    {
        return (int) substr($groupKey, strlen(self::GROUP_KEY_PREFIX));
    }
}
