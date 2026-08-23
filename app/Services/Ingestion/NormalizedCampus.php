<?php

namespace App\Services\Ingestion;

/**
 * A campus record extracted from a source payload, before validation/storage.
 */
final class NormalizedCampus
{
    public function __construct(
        public readonly string $name,
        public readonly string $city,
        public readonly string $region,
        public readonly ?string $address = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'city' => $this->city,
            'region' => $this->region,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
