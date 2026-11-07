<?php

namespace App\Actions;

class ParseIdRange
{
    /**
     * Parse a range/list string into an array of unique sorted positive integers.
     *
     * Supports:
     * - Single: "1"
     * - Comma-separated: "1,3,5"
     * - Range: "1-10"
     * - Mixed: "1-5,8,10-12"
     *
     * @return array<int>
     */
    public function handle(?string $input): array
    {
        if ($input === null || trim($input) === '') {
            return [];
        }

        $segments = explode(',', $input);
        $ids = [];

        foreach ($segments as $segment) {
            $trimmed = trim($segment);

            if ($trimmed === '') {
                continue;
            }

            if (str_contains($trimmed, '-')) {
                $range = explode('-', $trimmed);

                // Ensure exactly one hyphen separating two parts
                if (count($range) === 2) {
                    $startStr = trim($range[0]);
                    $endStr = trim($range[1]);

                    if (ctype_digit($startStr) && ctype_digit($endStr)) {
                        $start = (int) $startStr;
                        $end = (int) $endStr;

                        if ($start <= $end) {
                            foreach (range($start, $end) as $id) {
                                $ids[] = $id;
                            }
                        }
                    }
                }
            } elseif (ctype_digit($trimmed)) {
                $ids[] = (int) $trimmed;
            }
        }

        $uniqueIds = array_values(array_unique($ids, SORT_NUMERIC));
        sort($uniqueIds, SORT_NUMERIC);

        return $uniqueIds;
    }
}
