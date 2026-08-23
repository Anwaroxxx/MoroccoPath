<?php

namespace App\Services\Ingestion;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Downloads a payload from the source's official endpoint and archives it
 * under data/raw/{slug}/ for auditability (spec §23). Raw payloads are never
 * parsed straight into production tables.
 */
final class HttpFetcher implements Fetcher
{
    public function fetch(SourceDefinition $definition): string
    {
        $endpoint = $definition->endpoint();

        if ($endpoint === null || $endpoint === '') {
            throw new RuntimeException(sprintf(
                'Source [%s] has no endpoint configured. Set "endpoint" in data/sources/%s.json or ingest with --fixture.',
                $definition->slug(),
                $definition->slug(),
            ));
        }

        $body = Http::timeout(30)
            ->retry(2, 1000)
            ->throw()
            ->get($endpoint)
            ->body();

        self::archive($definition->slug(), $body);

        return $body;
    }

    public static function archive(string $slug, string $body): void
    {
        $directory = base_path('data/raw/'.$slug);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents(
            $directory.'/'.now()->format('Ymd-His').'.payload',
            $body,
        );
    }
}
