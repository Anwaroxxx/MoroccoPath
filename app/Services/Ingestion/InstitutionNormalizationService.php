<?php

namespace App\Services\Ingestion;

/**
 * Produces canonical display values for institution records (spec §24):
 * the most complete name wins as canonical, every other known name becomes
 * a searchable alias.
 */
final class InstitutionNormalizationService
{
    /**
     * @param  array<int, string>  $names
     */
    public function canonicalName(array $names): string
    {
        $candidates = array_values(array_filter(array_map(
            TextNormalizer::clean(...),
            $names,
        )));

        if ($candidates === []) {
            return '';
        }

        // Longest name is assumed most complete ("ISTA Casa" -> full title).
        usort($candidates, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        return $candidates[0];
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    public function aliases(array $names, string $canonical): array
    {
        return array_values(array_unique(array_filter(array_map(
            TextNormalizer::clean(...),
            array_diff($names, [$canonical]),
        ))));
    }

    public function slug(string $canonicalName): string
    {
        return TextNormalizer::slug($canonicalName);
    }
}
