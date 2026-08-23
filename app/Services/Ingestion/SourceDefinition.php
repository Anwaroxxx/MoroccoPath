<?php

namespace App\Services\Ingestion;

use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Loads and validates a source definition from data/sources/{slug}.json.
 * Definitions describe WHERE data comes from and HOW it looks — never the
 * records themselves.
 */
final class SourceDefinition
{
    /**
     * @param  array<string, mixed>  $config
     */
    private function __construct(private readonly array $config) {}

    public static function load(string $slug): self
    {
        $path = base_path('data/sources/'.$slug.'.json');

        if (! is_file($path)) {
            throw new InvalidArgumentException("Unknown ingestion source [{$slug}]: missing {$path}");
        }

        $config = json_decode((string) file_get_contents($path), true);

        if (! is_array($config)) {
            throw new InvalidArgumentException("Invalid JSON in source definition [{$path}]");
        }

        foreach (['slug', 'name', 'source_type', 'format'] as $required) {
            if (! Arr::has($config, $required) || Arr::get($config, $required) === null) {
                throw new InvalidArgumentException("Source definition [{$slug}] is missing required key [{$required}]");
            }
        }

        return new self($config);
    }

    public function slug(): string
    {
        return (string) $this->config['slug'];
    }

    public function name(): string
    {
        return (string) $this->config['name'];
    }

    public function sourceType(): string
    {
        return (string) $this->config['source_type'];
    }

    public function website(): ?string
    {
        return Arr::get($this->config, 'website');
    }

    public function endpoint(): ?string
    {
        return Arr::get($this->config, 'endpoint');
    }

    public function format(): string
    {
        return (string) $this->config['format'];
    }

    public function defaultAcademicYear(): string
    {
        return (string) Arr::get($this->config, 'default_academic_year', '2026/2027');
    }
}
