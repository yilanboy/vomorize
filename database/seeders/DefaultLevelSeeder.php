<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class DefaultLevelSeeder extends Seeder
{
    private const array LEVELS = [
        1 => [
            ['locale' => 'zh-tw', 'name' => '等級 1', 'description' => '基礎入門：核心常用 1,000 單字'],
            ['locale' => 'zh-cn', 'name' => '等级 1', 'description' => '基础入门：核心常用 1,000 单词'],
            ['locale' => 'ja', 'name' => 'レベル 1', 'description' => '初級：よく使われる必須1,000単語'],
        ],
        2 => [
            ['locale' => 'zh-tw', 'name' => '等級 2', 'description' => '初級進階：日常溝通必備詞彙'],
            ['locale' => 'zh-cn', 'name' => '等级 2', 'description' => '初级进阶：日常沟通必备词汇'],
            ['locale' => 'ja', 'name' => 'レベル 2', 'description' => '初中級：日常会話に必要な必須単語'],
        ],
        3 => [
            ['locale' => 'zh-tw', 'name' => '等級 3', 'description' => '中級應用：掌握生活與社交話題'],
            ['locale' => 'zh-cn', 'name' => '等级 3', 'description' => '中级应用：掌握生活与社交话题'],
            ['locale' => 'ja', 'name' => 'レベル 3', 'description' => '中級：日常・社会生活の話題に対応できる単語'],
        ],
        4 => [
            ['locale' => 'zh-tw', 'name' => '等級 4', 'description' => '中高級加強：理解複雜議題與表達'],
            ['locale' => 'zh-cn', 'name' => '等级 4', 'description' => '中高级加强：理解复杂议题与表达'],
            ['locale' => 'ja', 'name' => 'レベル 4', 'description' => '中上級：複雑なトピックを理解・表現する単語'],
        ],
        5 => [
            ['locale' => 'zh-tw', 'name' => '等級 5', 'description' => '高級深造：學術與專業領域詞彙'],
            ['locale' => 'zh-cn', 'name' => '等级 5', 'description' => '高级深造：学术与专业领域词汇'],
            ['locale' => 'ja', 'name' => 'レベル 5', 'description' => '上級：学術・専門分野の重要単語'],
        ],
        6 => [
            ['locale' => 'zh-tw', 'name' => '等級 6', 'description' => '大師精通：流暢運用高難度核心詞彙'],
            ['locale' => 'zh-cn', 'name' => '等级 6', 'description' => '大师精通：流畅运用高难度核心词汇'],
            ['locale' => 'ja', 'name' => 'レベル 6', 'description' => '最上級：高度な核心単語を自在に使いこなす'],
        ],
        7 => [
            ['locale' => 'zh-tw', 'name' => '等級 7', 'description' => '額外補充：進階加分詞彙'],
            ['locale' => 'zh-cn', 'name' => '等级 7', 'description' => '额外补充：进阶加分词汇'],
            ['locale' => 'ja', 'name' => 'レベル 7', 'description' => '補足：応用・スコアアップ単語'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::LEVELS as $id => $translations) {
            $level = Level::query()->updateOrCreate(['id' => $id]);
            $level->translations()->upsert($translations, uniqueBy: ['level_id', 'locale']);
        }
    }
}
