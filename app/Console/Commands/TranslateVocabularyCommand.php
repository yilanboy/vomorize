<?php

namespace App\Console\Commands;

use App\Ai\Agents\VocabularyTranslatorAgent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Laravel\Ai\Enums\Lab;

class TranslateVocabularyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vocabulary:translate
                            {--level= : Specific level number(s) to process, separated by comma (e.g. 1 or 1,2)}
                            {--locales= : Specific locale(s) to translate, separated by comma (e.g. zh-cn,ja,zh-tw or zh-tw)}
                            {--locale= : Alias for --locales}
                            {--model=gemini-3.6-flash : AI model to use}
                            {--batch-size=20 : Number of items per AI request}
                            {--dry-run : Output AI sample responses without writing files}
                            {--force : Re-translate items even if target locale translations already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill translations for vocabulary data using Laravel AI Gemini';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $levelsInput = $this->option('level');
        $localesInput = $this->option('locales') ?: $this->option('locale');
        $model = $this->option('model');
        $batchSize = max(1, (int) $this->option('batch-size'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $levels = $this->resolveLevels($levelsInput);
        $targetLocales = $this->resolveLocales($localesInput);

        if (empty($levels)) {
            $this->error('No valid level files found.');

            return self::FAILURE;
        }

        $this->info(
            sprintf(
                'Starting vocabulary translation (Batch Size: %d, Levels: %s, Locales: %s, Dry Run: %s, Force: %s)',
                $batchSize,
                implode(', ', $levels),
                implode(', ', $targetLocales),
                $dryRun ? 'Yes' : 'No',
                $force ? 'Yes' : 'No'
            )
        );

        foreach ($levels as $level) {
            $filePath = database_path("data/vocabulary/level_{$level}.php");

            if (! file_exists($filePath)) {
                $this->warn("Level {$level} file not found at {$filePath}, skipping.");

                continue;
            }

            $this->processLevelFile($filePath, $level, $batchSize, $targetLocales, $model, $dryRun, $force);
        }

        $this->info('Vocabulary translation process completed.');

        return self::SUCCESS;
    }

    /**
     * Resolve target locale codes.
     *
     * @return string[]
     */
    protected function resolveLocales(?string $input): array
    {
        if ($input !== null && $input !== '') {
            $parts = explode(',', $input);
            $locales = [];
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if ($trimmed !== '') {
                    $locales[] = $trimmed;
                }
            }

            return array_values(array_unique($locales));
        }

        return ['zh-cn', 'ja'];
    }

    /**
     * Resolve target level numbers.
     *
     * @return int[]
     */
    protected function resolveLevels(?string $input): array
    {
        if ($input !== null && $input !== '') {
            $parts = explode(',', $input);
            $levels = [];
            foreach ($parts as $part) {
                $num = (int) trim($part);
                if ($num > 0) {
                    $levels[] = $num;
                }
            }

            return array_values(array_unique($levels));
        }

        $files = glob(database_path('data/vocabulary/level_*.php'));
        $levels = [];

        foreach ($files as $file) {
            if (preg_match('/level_(\d+)\.php$/', $file, $matches)) {
                $levels[] = (int) $matches[1];
            }
        }

        sort($levels);

        return $levels;
    }

    /**
     * Process a single-level file.
     *
     * @param  string[]  $targetLocales
     */
    protected function processLevelFile(
        string $filePath,
        int $level,
        int $batchSize,
        array $targetLocales,
        string $model,
        bool $dryRun,
        bool $force
    ): void {
        $this->components->info("Processing Level {$level}...");

        $data = require $filePath;

        // Collect all items needing translation with references to group and item index
        $pending = [];

        foreach ($data as $groupKey => $items) {
            foreach ($items as $itemIndex => $item) {
                $needsTranslation = false;

                if ($force) {
                    $needsTranslation = true;
                } else {
                    foreach ($targetLocales as $locale) {
                        if (! isset($item['translations'][$locale]['definition']) || $item['translations'][$locale]['definition'] === '') {
                            $needsTranslation = true;
                            break;
                        }
                    }
                }

                if ($needsTranslation) {
                    $pending[] = [
                        'group' => $groupKey,
                        'index' => $itemIndex,
                        'item' => $item,
                    ];
                }
            }
        }

        if (empty($pending)) {
            $this->info("Level {$level} has no pending items to translate.");

            return;
        }

        $totalPending = count($pending);
        $this->info("Found {$totalPending} pending items for Level {$level}.");

        $bar = $this->output->createProgressBar($totalPending);
        $bar->start();

        $chunks = array_chunk($pending, $batchSize);
        $agent = VocabularyTranslatorAgent::make();

        foreach ($chunks as $chunk) {
            $payload = array_map(function ($entry) {
                $item = $entry['item'];

                return [
                    'word' => $item['word'],
                    'part_of_speech' => $item['part_of_speech'],
                    'pronunciation' => $item['pronunciation'] ?? '',
                    'example_sentence' => $item['example_sentence'],
                    'existing_translations' => $item['translations'] ?? [],
                ];
            }, $chunk);

            $localesList = implode(', ', $targetLocales);
            $prompt = "Translate the following English vocabulary items into target locales ({$localesList}).\n"
                ."All translated definitions across target locales must reflect the exact same meaning as demonstrated in the example sentence and any existing translations.\n\n"
                .json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            try {
                $response = $agent->prompt(
                    $prompt,
                    attachments: [],
                    provider: Lab::Gemini,
                );

                $translations = $response['translations'] ?? [];

                // Map results back to data array
                foreach ($chunk as $indexInChunk => $entry) {
                    $groupKey = $entry['group'];
                    $itemIndex = $entry['index'];
                    $originalWord = $entry['item']['word'];

                    // Find translation matching word or by index fallback
                    $translated = $translations[$indexInChunk] ?? null;
                    if (! $translated || (isset($translated['word']) && strcasecmp(
                        $translated['word'],
                        $originalWord
                    ) !== 0)) {
                        foreach ($translations as $t) {
                            if (isset($t['word']) && strcasecmp($t['word'], $originalWord) === 0) {
                                $translated = $t;
                                break;
                            }
                        }
                    }

                    if ($translated) {
                        if (isset($translated['results']) && is_array($translated['results'])) {
                            foreach ($translated['results'] as $res) {
                                if (isset($res['locale'], $res['definition'], $res['example_translation'])) {
                                    $data[$groupKey][$itemIndex]['translations'][$res['locale']] = [
                                        'definition' => $res['definition'],
                                        'example_translation' => $res['example_translation'],
                                    ];
                                }
                            }
                        }

                        foreach ($targetLocales as $locale) {
                            if (isset($translated[$locale]['definition'], $translated[$locale]['example_translation'])) {
                                $data[$groupKey][$itemIndex]['translations'][$locale] = [
                                    'definition' => $translated[$locale]['definition'],
                                    'example_translation' => $translated[$locale]['example_translation'],
                                ];
                            }
                        }
                    }
                }

                if (! $dryRun) {
                    $this->saveLevelFile($filePath, $data);
                }
            } catch (\Throwable $e) {
                $this->error("\nError processing batch in Level {$level}: ".$e->getMessage());
            }

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->output->newLine();

        if (! $dryRun) {
            $this->runPint($filePath);
            $this->info("Level {$level} updated and formatted.");
        } else {
            $this->info("Level {$level} dry-run complete (no changes saved).");
        }
    }

    /**
     * Save the updated PHP array to a file.
     */
    protected function saveLevelFile(string $filePath, array $data): void
    {
        $content = "<?php\n\nreturn ".$this->customVarExport($data).";\n";
        file_put_contents($filePath, $content);
    }

    /**
     * Run Pint auto-formatter on the specified file.
     */
    protected function runPint(string $filePath): void
    {
        Process::run(['vendor/bin/pint', $filePath]);
    }

    /**
     * Format PHP array cleanly with short array syntax.
     */
    protected function customVarExport(array $data, int $indent = 0): string
    {
        $spaces = str_repeat('    ', $indent);
        $lines = [];
        $isIndexed = array_is_list($data);

        foreach ($data as $key => $value) {
            $keyFormatted = $isIndexed ? '' : var_export($key, true).' => ';
            if (is_array($value)) {
                $valueFormatted = $this->customVarExport($value, $indent + 1);
                $lines[] = "{$spaces}    {$keyFormatted}{$valueFormatted}";
            } else {
                $valueFormatted = var_export($value, true);
                $lines[] = "{$spaces}    {$keyFormatted}{$valueFormatted}";
            }
        }

        $inner = implode(",\n", $lines);
        if ($inner !== '') {
            $inner .= ',';
        }

        return "[\n{$inner}\n{$spaces}]";
    }
}
