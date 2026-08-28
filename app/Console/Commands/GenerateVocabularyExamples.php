<?php

namespace App\Console\Commands;

use App\Ai\Agents\VocabularyExampleAgent;
use Illuminate\Console\Command;
use Laravel\Ai\Enums\Lab;

class GenerateVocabularyExamples extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vocabulary:generate-examples
                            {--level= : Specific level number(s) to process, separated by comma (e.g. 1 or 1,2)}
                            {--file= : Specific CSV file path to process}
                            {--provider=openai : AI provider to use (e.g. openai, gemini, anthropic)}
                            {--model=gpt-5.6-luna : AI model to use}
                            {--batch-size=20 : Number of items per AI request}
                            {--dry-run : Output AI sample responses without writing files}
                            {--force : Re-generate examples even if already present}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate example sentences and Traditional Chinese translations for vocabulary CSV files using OpenAI GPT-5.6 Luna';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $levelsInput = $this->option('level');
        $fileInput = $this->option('file');
        $providerInput = (string) ($this->option('provider') ?: 'openai');
        $model = (string) ($this->option('model') ?: 'gpt-5.6-luna');
        $batchSize = max(1, (int) $this->option('batch-size'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $provider = Lab::tryFrom(strtolower($providerInput)) ?? Lab::OpenAI;

        $files = $this->resolveFiles($fileInput, $levelsInput);

        if (empty($files)) {
            $this->error('No valid vocabulary CSV files found to process.');

            return self::FAILURE;
        }

        $this->info(
            sprintf(
                'Starting vocabulary example generation (Files: %d, Batch Size: %d, Provider: %s, Model: %s, Dry Run: %s, Force: %s)',
                count($files),
                $batchSize,
                $provider->value,
                $model,
                $dryRun ? 'Yes' : 'No',
                $force ? 'Yes' : 'No'
            )
        );

        foreach ($files as $filePath) {
            $this->processCsvFile($filePath, $batchSize, $provider, $model, $dryRun, $force);
        }

        $this->info('Vocabulary example generation process completed.');

        return self::SUCCESS;
    }

    /**
     * Resolve target CSV files to process.
     *
     * @return string[]
     */
    protected function resolveFiles(?string $fileInput, ?string $levelsInput): array
    {
        if ($fileInput !== null && $fileInput !== '') {
            $resolvedPath = file_exists($fileInput) ? $fileInput : base_path($fileInput);
            if (file_exists($resolvedPath)) {
                return [$resolvedPath];
            }

            $this->warn("Specified file not found at: {$fileInput}");

            return [];
        }

        $levelNumbers = $this->resolveLevels($levelsInput);
        $files = [];

        foreach ($levelNumbers as $level) {
            $path = database_path("data/vocabulary/new_level_{$level}.csv");
            if (file_exists($path)) {
                $files[] = $path;
            } else {
                $this->warn("CSV file for Level {$level} not found at {$path}, skipping.");
            }
        }

        return $files;
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

        $discoveredFiles = glob(database_path('data/vocabulary/new_level_*.csv')) ?: [];
        $levels = [];

        foreach ($discoveredFiles as $file) {
            if (preg_match('/new_level_(\d+)\.csv$/', $file, $matches)) {
                $levels[] = (int) $matches[1];
            }
        }

        sort($levels);

        return $levels;
    }

    /**
     * Process a single CSV file.
     */
    protected function processCsvFile(
        string $filePath,
        int $batchSize,
        Lab $provider,
        string $model,
        bool $dryRun,
        bool $force
    ): void {
        $fileName = basename($filePath);
        $this->components->info("Processing {$fileName}...");

        if (! is_readable($filePath)) {
            $this->error("Cannot read file: {$filePath}");

            return;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Failed to open file: {$filePath}");

            return;
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            $this->warn("Empty or invalid CSV file: {$filePath}");

            return;
        }

        // Clean headers of BOM or surrounding whitespace
        $headers = array_map(function ($h) {
            return trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $h));
        }, $headers);

        $wordCol = $this->findColumnIndex($headers, ['英文單字', 'word', 'english'], 0);
        $posCol = $this->findColumnIndex($headers, ['詞性', 'pos', 'part_of_speech'], 1);
        $defCol = $this->findColumnIndex($headers, ['中文意思', '中文', 'definition', 'meaning'], 2);
        $levelCol = $this->findColumnIndex($headers, ['等級', 'level'], 3);
        $sentenceCol = $this->findColumnIndex($headers, ['例句', 'example', 'example_sentence', '英文例句'], -1);
        $translationCol = $this->findColumnIndex($headers, ['例句中文翻譯', '例句翻譯', '例句中文', '中文例句', 'example_translation', 'translation'], -1);

        // If sentence or translation columns do not exist in headers, append them
        if ($sentenceCol === -1) {
            $headers[] = '例句';
            $sentenceCol = count($headers) - 1;
        }
        if ($translationCol === -1) {
            $headers[] = '例句中文翻譯';
            $translationCol = count($headers) - 1;
        }

        $rows = [];
        $pending = [];
        $rowIndex = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Pad row if it has fewer columns than headers
            while (count($row) < count($headers)) {
                $row[] = '';
            }

            $word = trim($row[$wordCol] ?? '');
            $pos = trim($row[$posCol] ?? '');
            $def = trim($row[$defCol] ?? '');
            $level = trim($row[$levelCol] ?? '');
            $existingSentence = trim($row[$sentenceCol] ?? '');
            $existingTranslation = trim($row[$translationCol] ?? '');

            $rows[$rowIndex] = $row;

            if ($word !== '') {
                $needsGeneration = $force || $existingSentence === '' || $existingTranslation === '';
                if ($needsGeneration) {
                    $pending[] = [
                        'row_index' => $rowIndex,
                        'word' => $word,
                        'part_of_speech' => $pos,
                        'definition' => $def,
                        'level' => $level,
                    ];
                }
            }

            $rowIndex++;
        }

        fclose($handle);

        if (empty($pending)) {
            $this->info("All vocabulary items in {$fileName} already have example sentences.");

            return;
        }

        $totalPending = count($pending);
        $this->info("Found {$totalPending} pending items to process in {$fileName}.");

        $bar = $this->output->createProgressBar($totalPending);
        $bar->start();

        /** @var int<1, max> $chunkSize */
        $chunkSize = max(1, $batchSize);
        $chunks = array_chunk($pending, $chunkSize);
        $agent = VocabularyExampleAgent::make();

        foreach ($chunks as $chunk) {
            $payload = array_map(function ($entry) {
                return [
                    'word' => $entry['word'],
                    'part_of_speech' => $entry['part_of_speech'],
                    'definition' => $entry['definition'],
                    'level' => $entry['level'],
                ];
            }, $chunk);

            $prompt = "Generate a level-appropriate English example sentence and Traditional Chinese (繁體中文) translation for the following vocabulary items:\n\n"
                .json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            try {
                $response = $agent->prompt(
                    $prompt,
                    provider: $provider,
                    model: $model,
                );

                /** @var array<int, array{word?: string, example_sentence?: string, example_translation?: string}> $results */
                $results = $response['results'] ?? []; // @phpstan-ignore offsetAccess.nonOffsetAccessible

                foreach ($chunk as $indexInChunk => $entry) {
                    $rIndex = $entry['row_index'];
                    $targetWord = $entry['word'];

                    $matchedResult = $results[$indexInChunk] ?? null;
                    if (! $matchedResult || (isset($matchedResult['word']) && strcasecmp($matchedResult['word'], $targetWord) !== 0)) {
                        foreach ($results as $res) {
                            if (isset($res['word']) && strcasecmp($res['word'], $targetWord) === 0) {
                                $matchedResult = $res;
                                break;
                            }
                        }
                    }

                    if ($matchedResult && ! empty($matchedResult['example_sentence'])) {
                        $rows[$rIndex][$sentenceCol] = $matchedResult['example_sentence'];
                        $rows[$rIndex][$translationCol] = $matchedResult['example_translation'] ?? '';
                    }
                }

                if (! $dryRun) {
                    $this->saveCsvFile($filePath, $headers, $rows);
                }
            } catch (\Throwable $e) {
                $this->error("\nError processing batch in {$fileName}: ".$e->getMessage());
            }

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->output->newLine();

        if (! $dryRun) {
            $this->info("{$fileName} updated successfully.");
        } else {
            $this->info("{$fileName} dry-run complete (no changes saved).");
        }
    }

    /**
     * Find column index by alias names or return default index.
     *
     * @param  string[]  $headers
     * @param  string[]  $aliases
     */
    protected function findColumnIndex(array $headers, array $aliases, int $default): int
    {
        foreach ($headers as $index => $header) {
            foreach ($aliases as $alias) {
                if (mb_strtolower($header) === mb_strtolower($alias)) {
                    return $index;
                }
            }
        }

        return $default;
    }

    /**
     * Save the CSV file safely.
     *
     * @param  string[]  $headers
     * @param  array<int, string[]>  $rows
     */
    protected function saveCsvFile(string $filePath, array $headers, array $rows): void
    {
        $tempPath = $filePath.'.tmp';
        $handle = fopen($tempPath, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Unable to write to temporary file: {$tempPath}");
        }

        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        if (! rename($tempPath, $filePath)) {
            throw new \RuntimeException("Unable to rename {$tempPath} to {$filePath}");
        }
    }
}
