<?php

namespace App\Services\Ingestion;

use App\Enums\InstitutionType;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Parses a raw JSON payload into normalized records.
 *
 * Expected payload shape:
 * {
 *   "institutions": [
 *     {
 *       "name": "...", "external_identifier": "...", "alt_names": ["..."],
 *       "type": "vocational_training", "website": "...", "phone": "...",
 *       "email": "...",
 *       "campuses": [{"name": "...", "city": "...", "region": "..."}],
 *       "programs": [{"name": "...", "study_mode": "full_time", ...}]
 *     }
 *   ]
 * }
 */
final class JsonPayloadParser
{
    /**
     * @return array<int, NormalizedInstitution>
     */
    public function parse(string $body): array
    {
        $payload = json_decode($body, true);

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Payload is not valid JSON.');
        }

        $records = Arr::get($payload, 'institutions');

        if (! is_array($records)) {
            throw new InvalidArgumentException('Payload is missing the "institutions" array.');
        }

        return array_values(array_map(
            fn (array $record): NormalizedInstitution => $this->parseInstitution($record),
            $records,
        ));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function parseInstitution(array $record): NormalizedInstitution
    {
        $campuses = [];

        foreach ((array) Arr::get($record, 'campuses', []) as $campus) {
            if (! is_array($campus)) {
                continue;
            }

            $campuses[] = new NormalizedCampus(
                name: TextNormalizer::clean((string) Arr::get($campus, 'name', '')),
                city: TextNormalizer::clean((string) Arr::get($campus, 'city', '')),
                region: TextNormalizer::clean((string) Arr::get($campus, 'region', '')),
                address: Arr::get($campus, 'address') !== null ? TextNormalizer::clean((string) $campus['address']) : null,
                latitude: Arr::get($campus, 'latitude') !== null ? (float) $campus['latitude'] : null,
                longitude: Arr::get($campus, 'longitude') !== null ? (float) $campus['longitude'] : null,
            );
        }

        $programs = [];

        foreach ((array) Arr::get($record, 'programs', []) as $program) {
            if (! is_array($program)) {
                continue;
            }

            $programs[] = new NormalizedProgram(
                name: TextNormalizer::clean((string) Arr::get($program, 'name', '')),
                externalIdentifier: Arr::get($program, 'external_identifier'),
                studyMode: Arr::get($program, 'study_mode'),
                durationMonths: Arr::get($program, 'duration_months') !== null ? (int) $program['duration_months'] : null,
                durationLabel: Arr::get($program, 'duration_label') !== null ? TextNormalizer::clean((string) $program['duration_label']) : null,
                language: Arr::get($program, 'language'),
                levelCode: Arr::get($program, 'level'),
                description: Arr::get($program, 'description'),
                academicYear: Arr::get($program, 'academic_year'),
                interests: array_values(array_map(strval(...), (array) Arr::get($program, 'interests', []))),
                skills: array_values(array_map(strval(...), (array) Arr::get($program, 'skills', []))),
                admissionInformation: Arr::get($program, 'admission_information') !== null
                    ? TextNormalizer::clean((string) $program['admission_information'])
                    : null,
                sourceUrl: Arr::get($program, 'source_url'),
                costs: array_values(array_filter(
                    array_map(fn ($cost): ?array => is_array($cost) ? $cost : null,
                        (array) Arr::get($program, 'costs', [])),
                )),
                rules: array_values(array_filter(
                    array_map(fn ($rule): ?array => is_array($rule) ? $rule : null,
                        (array) Arr::get($program, 'rules', [])),
                )),
            );
        }

        $type = Arr::get($record, 'type', InstitutionType::Other->value);
        InstitutionType::tryFrom((string) $type)
            ?? throw new InvalidArgumentException(sprintf(
                'Unknown institution type [%s] for record [%s].',
                (string) $type,
                (string) Arr::get($record, 'name', '?'),
            ));

        return new NormalizedInstitution(
            name: TextNormalizer::clean((string) Arr::get($record, 'name', '')),
            altNames: array_values(array_map(
                fn ($alias): string => TextNormalizer::clean((string) $alias),
                (array) Arr::get($record, 'alt_names', []),
            )),
            externalIdentifier: Arr::get($record, 'external_identifier'),
            type: (string) $type,
            website: Arr::get($record, 'website'),
            phone: Arr::get($record, 'phone'),
            email: Arr::get($record, 'email'),
            description: Arr::get($record, 'description'),
            campuses: $campuses,
            programs: $programs,
            sourceUrl: Arr::get($record, 'source_url'),
        );
    }
}
