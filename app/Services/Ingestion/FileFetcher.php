<?php

namespace App\Services\Ingestion;

use InvalidArgumentException;

/**
 * Reads a payload from a local file — used for fixture-based ingestion,
 * offline processing of previously archived payloads, and tests.
 */
final class FileFetcher implements Fetcher
{
    public function __construct(private readonly string $path) {}

    public function fetch(SourceDefinition $definition): string
    {
        if (! is_file($this->path)) {
            throw new InvalidArgumentException("Fixture payload not found [{$this->path}]");
        }

        return (string) file_get_contents($this->path);
    }
}
