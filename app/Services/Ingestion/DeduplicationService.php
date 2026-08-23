<?php

namespace App\Services\Ingestion;

use App\Models\Institution;
use App\Models\InstitutionAlias;

/**
 * Canonical entity resolution (spec §24). Matching order:
 * 1. external_identifier (authoritative)
 * 2. slug
 * 3. normalized canonical name key
 * 4. registered alias keys ("ISTA Casa" -> the ISTA Casablanca entity)
 *
 * A match merges into the existing entity by registering new aliases;
 * only a true miss creates a new institution.
 */
final class DeduplicationService
{
    public function __construct(private readonly InstitutionNormalizationService $normalizer) {}

    /**
     * Resolves the record to a canonical institution, creating one only if
     * nothing matches. Returns [institution, created].
     *
     * @return array{0: Institution, 1: bool}
     */
    public function resolveInstitution(NormalizedInstitution $record): array
    {
        $names = $record->allNames();
        $canonical = $this->normalizer->canonicalName($names);

        $existing = $this->findByExternalIdentifier($record)
            ?? $this->findByNameKeys(array_merge([$canonical], $names));

        $aliases = $this->normalizer->aliases($names, $canonical);

        if ($existing !== null) {
            foreach ($aliases as $alias) {
                InstitutionAlias::query()->firstOrCreate([
                    'institution_id' => $existing->id,
                    'alias' => $alias,
                ]);
            }

            return [$existing->refresh(), false];
        }

        $institution = Institution::create([
            'name' => TextNormalizer::clean($record->name),
            'canonical_name' => $canonical,
            'slug' => $this->uniqueSlug($this->normalizer->slug($canonical)),
            'type' => $record->type,
            'status' => 'unknown',
            'website' => $record->website,
            'phone' => $record->phone,
            'email' => $record->email,
            'description' => $record->description,
            'external_identifier' => $record->externalIdentifier,
        ]);

        foreach ($aliases as $alias) {
            InstitutionAlias::query()->firstOrCreate([
                'institution_id' => $institution->id,
                'alias' => $alias,
            ]);
        }

        return [$institution, true];
    }

    private function findByExternalIdentifier(NormalizedInstitution $record): ?Institution
    {
        if ($record->externalIdentifier === null) {
            return null;
        }

        return Institution::query()
            ->where('external_identifier', $record->externalIdentifier)
            ->first();
    }

    /**
     * Matches any known name (canonical or alias) against stored entities
     * using accent/case/punctuation-insensitive keys.
     *
     * @param  array<int, string>  $candidateNames
     */
    private function findByNameKeys(array $candidateNames): ?Institution
    {
        $keys = array_filter(array_map(TextNormalizer::key(...), $candidateNames));

        if ($keys === []) {
            return null;
        }

        $institutions = Institution::query()->get(['id', 'canonical_name']);
        $byKey = [];

        foreach ($institutions as $institution) {
            $byKey[TextNormalizer::key($institution->canonical_name)] = $institution->id;
        }

        foreach (InstitutionAlias::query()->get(['institution_id', 'alias']) as $alias) {
            $byKey[TextNormalizer::key($alias->alias)] ??= $alias->institution_id;
        }

        foreach ($keys as $key) {
            if (isset($byKey[$key])) {
                return Institution::query()->findOrFail($byKey[$key]);
            }
        }

        return null;
    }

    /**
     * Slug collisions with a DIFFERENT entity get a deterministic suffix.
     */
    private function uniqueSlug(string $base): string
    {
        if (! Institution::query()->where('slug', $base)->exists()) {
            return $base;
        }

        $suffix = 2;

        while (Institution::query()->where('slug', $base.'-'.$suffix)->exists()) {
            $suffix++;
        }

        return $base.'-'.$suffix;
    }
}
