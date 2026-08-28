<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DefaultVocabularySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(DefaultLevelSeeder::class);
        $this->callWith(DefaultLevelVocabularySeeder::class, ['levelId' => 1]);
        $this->callWith(DefaultLevelVocabularySeeder::class, ['levelId' => 2]);
        $this->callWith(DefaultLevelVocabularySeeder::class, ['levelId' => 3]);
        $this->callWith(DefaultLevelVocabularySeeder::class, ['levelId' => 4]);
        $this->callWith(DefaultLevelVocabularySeeder::class, ['levelId' => 5]);
        $this->callWith(DefaultLevelVocabularySeeder::class, ['levelId' => 6]);
        $this->callWith(DefaultLevelVocabularySeeder::class, ['levelId' => 7]);
    }
}
