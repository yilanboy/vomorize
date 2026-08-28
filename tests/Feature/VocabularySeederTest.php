<?php

use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Database\Seeders\DefaultVocabularySeeder;

it('seeds all levels, groups, vocabularies and translations', function () {
    $this->seed(DefaultVocabularySeeder::class);

    expect(Level::count())->toBe(7)
        ->and(Group::count())->toBe(700)
        ->and(Vocabulary::count())->toBe(7000)
        ->and(VocabularyTranslation::where('locale', 'zh_TW')->count())->toBe(7000)
        ->and(VocabularyTranslation::where('locale', 'zh_CN')->count())->toBe(7000)
        ->and(VocabularyTranslation::where('locale', 'ja')->count())->toBe(7000);
});

it('seeds vocabulary fields and translations correctly', function () {
    $this->seed(DefaultVocabularySeeder::class);

    $group = Group::where('level_id', 1)->where('sequence', 1)->firstOrFail();
    $vocabulary = Vocabulary::where('group_id', $group->id)->where('word', 'sheep')->firstOrFail();

    expect($vocabulary->part_of_speech)->toBe('n.')
        ->and($vocabulary->pronunciation)->toBe('/ˈʃip/')
        ->and($vocabulary->example_sentence)->toBe('The sheep are eating grass in the field.');

    $translationTw = $vocabulary->translations()->where('locale', 'zh_TW')->firstOrFail();
    expect($translationTw->definition)->toBe('綿羊')
        ->and($translationTw->example_translation)->toBe('綿羊正在田野裡吃草。');

    $translationCn = $vocabulary->translations()->where('locale', 'zh_CN')->firstOrFail();
    expect($translationCn->definition)->toBe('绵羊')
        ->and($translationCn->example_translation)->toBe('绵羊正在田野里吃草。');

    $translationJa = $vocabulary->translations()->where('locale', 'ja')->firstOrFail();
    expect($translationJa->definition)->toBe('羊、ヒツジ')
        ->and($translationJa->example_translation)->toBe('羊たちは野原で草を食べています。');

    $group2 = Group::where('level_id', 2)->where('sequence', 1)->firstOrFail();
    $vocabulary2 = Vocabulary::where('group_id', $group2->id)->where('word', 'whose')->firstOrFail();

    expect($vocabulary2->part_of_speech)->toBe('pron.')
        ->and($vocabulary2->pronunciation)->toBe('/ˈhuz/')
        ->and($vocabulary2->example_sentence)->toBe('Whose book is this on the desk?');

    $translation2Tw = $vocabulary2->translations()->where('locale', 'zh_TW')->firstOrFail();
    expect($translation2Tw->definition)->toBe('誰的')
        ->and($translation2Tw->example_translation)->toBe('桌上的這本書是誰的？');

    $translation2Cn = $vocabulary2->translations()->where('locale', 'zh_CN')->firstOrFail();
    expect($translation2Cn->definition)->toBe('谁的')
        ->and($translation2Cn->example_translation)->toBe('桌上的这本书是谁的？');

    $translation2Ja = $vocabulary2->translations()->where('locale', 'ja')->firstOrFail();
    expect($translation2Ja->definition)->toBe('だれの、誰の')
        ->and($translation2Ja->example_translation)->toBe('机の上にあるこの本は誰のものですか？');

    $group3 = Group::where('level_id', 3)->where('sequence', 1)->firstOrFail();
    $vocabulary3 = Vocabulary::where('group_id', $group3->id)->where('word', 'greedy')->firstOrFail();

    expect($vocabulary3->part_of_speech)->toBe('adj.')
        ->and($vocabulary3->pronunciation)->toBe('/ˈɡɹidi/')
        ->and($vocabulary3->example_sentence)
        ->toBe('The greedy boy ate all the cookies and shared none with his friends.');

    $translation3Tw = $vocabulary3->translations()->where('locale', 'zh_TW')->firstOrFail();
    expect($translation3Tw->definition)->toBe('貪心的；貪婪的')
        ->and($translation3Tw->example_translation)->toBe('那個貪心的男孩吃掉了所有的餅乾，一點也不分給朋友。');

    $translation3Cn = $vocabulary3->translations()->where('locale', 'zh_CN')->firstOrFail();
    expect($translation3Cn->definition)->toBe('贪心的；贪婪的')
        ->and($translation3Cn->example_translation)->toBe('那个贪心的男孩吃掉了所有的饼干，一点也不分给朋友。');

    $translation3Ja = $vocabulary3->translations()->where('locale', 'ja')->firstOrFail();
    expect($translation3Ja->definition)->toBe('欲深い、貪欲な')
        ->and($translation3Ja->example_translation)
        ->toBe('その欲張りな男の子はクッキーを全部食べてしまい、友達には一切分けませんでした。');

    $group4 = Group::where('level_id', 4)->where('sequence', 1)->firstOrFail();
    $vocabulary4 = Vocabulary::where('group_id', $group4->id)->where('word', 'available')->firstOrFail();

    expect($vocabulary4->part_of_speech)->toBe('adj.')
        ->and($vocabulary4->pronunciation)->toBe('/əˈveɪɫəbəɫ/')
        ->and($vocabulary4->example_sentence)->toBe('The library has several computers available for students to use.');

    $translation4Tw = $vocabulary4->translations()->where('locale', 'zh_TW')->firstOrFail();
    expect($translation4Tw->definition)->toBe('可取得的；有空的；現有的')
        ->and($translation4Tw->example_translation)->toBe('圖書館有幾台電腦可供學生使用。');

    $translation4Cn = $vocabulary4->translations()->where('locale', 'zh_CN')->firstOrFail();
    expect($translation4Cn->definition)->toBe('可取得的；有空的；现有的')
        ->and($translation4Cn->example_translation)->toBe('图书馆有几台电脑可供学生使用。');

    $translation4Ja = $vocabulary4->translations()->where('locale', 'ja')->firstOrFail();
    expect($translation4Ja->definition)->toBe('利用可能な、手に入る、手が空いている')
        ->and($translation4Ja->example_translation)->toBe('図書館には学生が利用できるコンピューターが何台かあります。');

    $group5 = Group::where('level_id', 5)->where('sequence', 1)->firstOrFail();
    $vocabulary5 = Vocabulary::where('group_id', $group5->id)->where('word', 'resolution')->firstOrFail();

    expect($vocabulary5->part_of_speech)->toBe('n.')
        ->and($vocabulary5->pronunciation)->toBe('/ˌɹɛzəˈɫuʃən/')
        ->and($vocabulary5->example_sentence)
        ->toBe('After much discussion, the committee reached a resolution to reduce plastic waste.');

    $translation5Tw = $vocabulary5->translations()->where('locale', 'zh_TW')->firstOrFail();
    expect($translation5Tw->definition)->toBe('決心；決議；解析度；解決')
        ->and($translation5Tw->example_translation)->toBe('經過多次討論後，委員會通過了一項減少塑膠垃圾的決議。');

    $translation5Cn = $vocabulary5->translations()->where('locale', 'zh_CN')->firstOrFail();
    expect($translation5Cn->definition)->toBe('决心；决议；分辨率；解决')
        ->and($translation5Cn->example_translation)->toBe('经过多次讨论后，委员会通过了一项减少塑料垃圾的决议。');

    $translation5Ja = $vocabulary5->translations()->where('locale', 'ja')->firstOrFail();
    expect($translation5Ja->definition)->toBe('決議、決心、解像度、解決')
        ->and($translation5Ja->example_translation)
        ->toBe('度重なる議論の後、委員会はプラスチックごみを削減する決議を採択しました。');

    $group6 = Group::where('level_id', 6)->where('sequence', 1)->firstOrFail();
    $vocabulary6 = Vocabulary::where('group_id', $group6->id)->where('word', 'enterprise')->firstOrFail();

    expect($vocabulary6->part_of_speech)->toBe('n.')
        ->and($vocabulary6->pronunciation)->toBe('/ˈɛnɝˌpɹaɪz/, /ˈɛntɝˌpɹaɪz/')
        ->and($vocabulary6->example_sentence)
        ->toBe('Her small enterprise grew into a successful international company.');

    $translation6Tw = $vocabulary6->translations()->where('locale', 'zh_TW')->firstOrFail();
    expect($translation6Tw->definition)->toBe('企業；事業心')
        ->and($translation6Tw->example_translation)->toBe('她的小型企業發展成一家成功的跨國公司。');

    $translation6Cn = $vocabulary6->translations()->where('locale', 'zh_CN')->firstOrFail();
    expect($translation6Cn->definition)->toBe('企业；事业；事业心')
        ->and($translation6Cn->example_translation)->toBe('她的小型企业发展成一家成功的跨国公司。');

    $translation6Ja = $vocabulary6->translations()->where('locale', 'ja')->firstOrFail();
    expect($translation6Ja->definition)->toBe('企業、事業、進取の気性')
        ->and($translation6Ja->example_translation)->toBe('彼女の小さな企業は成功した多国籍企業へと成長しました。');

    $group7 = Group::where('level_id', 7)->where('sequence', 1)->firstOrFail();
    $vocabulary7 = Vocabulary::where('group_id', $group7->id)->where('word', 'uncover')->firstOrFail();

    expect($vocabulary7->part_of_speech)->toBe('vt.')
        ->and($vocabulary7->pronunciation)->toBe('/ənˈkəvɝ/')
        ->and($vocabulary7->example_sentence)->toBe('The investigation uncovered evidence of widespread corruption.');

    $translation7Tw = $vocabulary7->translations()->where('locale', 'zh_TW')->firstOrFail();
    expect($translation7Tw->definition)->toBe('揭露')
        ->and($translation7Tw->example_translation)->toBe('這項調查揭露了普遍貪腐的證據。');

    $translation7Cn = $vocabulary7->translations()->where('locale', 'zh_CN')->firstOrFail();
    expect($translation7Cn->definition)->toBe('揭露；发现')
        ->and($translation7Cn->example_translation)->toBe('这项调查揭露了普遍贪腐的证据。');

    $translation7Ja = $vocabulary7->translations()->where('locale', 'ja')->firstOrFail();
    expect($translation7Ja->definition)->toBe('明らかにする、暴露する')
        ->and($translation7Ja->example_translation)->toBe('その調査により広範囲に及ぶ汚職の証拠が明らかになりました。');
});

it('does not duplicate or reassign ids when run twice', function () {
    $this->seed(DefaultVocabularySeeder::class);

    $vocabularyId = Vocabulary::where('word', 'sheep')->firstOrFail()->id;

    $this->seed(DefaultVocabularySeeder::class);

    expect(Level::count())->toBe(7)
        ->and(Group::count())->toBe(700)
        ->and(Vocabulary::count())->toBe(7000)
        ->and(VocabularyTranslation::where('locale', 'zh_TW')->count())->toBe(7000)
        ->and(Vocabulary::where('word', 'sheep')->firstOrFail()->id)->toBe($vocabularyId);
});
