<?php

namespace App\Services\Ingestion;

/**
 * Builds stable, deduplication-friendly identifiers for programs.
 */
final class ProgramNormalizationService
{
    public function slug(string $institutionSlug, NormalizedProgram $record): string
    {
        $base = TextNormalizer::slug($institutionSlug.'-'.$record->name);

        if ($record->externalIdentifier !== null) {
            return $base.'-'.TextNormalizer::key($record->externalIdentifier);
        }

        return $base;
    }
}
