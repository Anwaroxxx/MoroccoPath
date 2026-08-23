<?php

namespace App\Services\Ingestion;

/**
 * Outcome of one ingestion run. Every rejection is explicit and auditable.
 */
final class IngestionReport
{
    /**
     * @param  array<int, array{key: string, errors: array<int, string>}>  $rejected
     */
    public function __construct(
        public int $institutionsCreated = 0,
        public int $institutionsMerged = 0,
        public int $campusesUpserted = 0,
        public int $programsCreated = 0,
        public int $programsUpdated = 0,
        public array $rejected = [],
    ) {}

    /**
     * @param  array<int, string>  $errors
     */
    public function reject(string $key, array $errors): void
    {
        $this->rejected[] = ['key' => $key, 'errors' => $errors];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'institutions_created' => $this->institutionsCreated,
            'institutions_merged' => $this->institutionsMerged,
            'campuses_upserted' => $this->campusesUpserted,
            'programs_created' => $this->programsCreated,
            'programs_updated' => $this->programsUpdated,
            'rejected' => $this->rejected,
        ];
    }
}
