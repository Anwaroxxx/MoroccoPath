<?php

namespace App\Services\Ingestion;

/**
 * An institution record extracted from a source payload, before validation/storage.
 */
final class NormalizedInstitution
{
    /**
     * @param  array<int, string>  $altNames
     * @param  array<int, NormalizedCampus>  $campuses
     * @param  array<int, NormalizedProgram>  $programs
     */
    public function __construct(
        public readonly string $name,
        public readonly array $altNames = [],
        public readonly ?string $externalIdentifier = null,
        public readonly string $type = 'other',
        public readonly ?string $website = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $description = null,
        public readonly array $campuses = [],
        public readonly array $programs = [],
    ) {}

    /**
     * Every name this institution is known by in the source data.
     *
     * @return array<int, string>
     */
    public function allNames(): array
    {
        return array_values(array_unique(array_merge([$this->name], $this->altNames)));
    }
}
