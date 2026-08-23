<?php

namespace App\Services\Ingestion;

/**
 * A program record extracted from a source payload, before validation/storage.
 */
final class NormalizedProgram
{
    /**
     * @param  array<int, string>  $interests
     * @param  array<int, string>  $skills
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $externalIdentifier = null,
        public readonly ?string $studyMode = null,
        public readonly ?int $durationMonths = null,
        public readonly ?string $durationLabel = null,
        public readonly ?string $language = null,
        public readonly ?string $levelCode = null,
        public readonly ?string $description = null,
        public readonly ?string $academicYear = null,
        public readonly array $interests = [],
        public readonly array $skills = [],
    ) {}
}
