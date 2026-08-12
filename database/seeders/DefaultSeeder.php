<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(DefaultLevelSeeder::class);
        $this->callWith(DefaultVocabularySeeder::class, ['levelId' => 1]);
        $this->callWith(DefaultVocabularySeeder::class, ['levelId' => 2]);
        $this->callWith(DefaultVocabularySeeder::class, ['levelId' => 3]);
        $this->callWith(DefaultVocabularySeeder::class, ['levelId' => 4]);
        $this->callWith(DefaultVocabularySeeder::class, ['levelId' => 5]);
        $this->callWith(DefaultVocabularySeeder::class, ['levelId' => 6]);
        $this->callWith(DefaultVocabularySeeder::class, ['levelId' => 7]);
    }
}
