<?php

namespace App\Console\Commands;

use App\Services\Ingestion\DeduplicationService;
use App\Services\Ingestion\FileFetcher;
use App\Services\Ingestion\HttpFetcher;
use App\Services\Ingestion\IngestionPipeline;
use App\Services\Ingestion\InstitutionNormalizationService;
use App\Services\Ingestion\JsonPayloadParser;
use App\Services\Ingestion\ProgramNormalizationService;
use App\Services\Ingestion\RecordValidator;
use App\Services\Ingestion\SourceDefinition;
use Exception;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

/**
 * CLI-only ingestion entrypoint (never exposed through HTTP routes).
 * Usage:
 *   php artisan ingest:run ofppt --fixture=path/to/payload.json
 *   php artisan ingest:run ofppt            (requires configured endpoint)
 *   php artisan ingest:run ofppt --fixture=... --dry
 */
class IngestRunCommand extends Command
{
    protected $signature = 'ingest:run
                            {source : Source slug under data/sources}
                            {--fixture= : Read payload from a local file instead of HTTP}
                            {--dry : Validate and resolve without writing anything}
                            {--year= : Override the academic year stamped on records}';

    protected $description = 'Fetch, normalize, validate and store records from an ingestion source';

    public function handle(): int
    {
        $definition = SourceDefinition::load((string) $this->argument('source'));

        $fetcher = $this->option('fixture') !== null
            ? new FileFetcher((string) $this->option('fixture'))
            : new HttpFetcher;

        $pipeline = new IngestionPipeline(
            definition: $definition,
            fetcher: $fetcher,
            parser: new JsonPayloadParser,
            validator: new RecordValidator,
            deduplication: new DeduplicationService(new InstitutionNormalizationService),
            programNormalizer: new ProgramNormalizationService,
            institutionNormalizer: new InstitutionNormalizationService,
            dryRun: (bool) $this->option('dry'),
            yearOverride: $this->option('year') !== null ? (string) $this->option('year') : null,
        );

        try {
            $report = $pipeline->run();
        } catch (Exception $exception) {
            error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('dry')) {
            warning('Dry run — nothing was written.');
        }

        info(sprintf(
            'Institutions created: %d · merged: %d · campuses upserted: %d · programs created: %d · updated: %d · rejected: %d',
            $report->institutionsCreated,
            $report->institutionsMerged,
            $report->campusesUpserted,
            $report->programsCreated,
            $report->programsUpdated,
            count($report->rejected),
        ));

        foreach ($report->rejected as $rejection) {
            warning(sprintf('REJECTED [%s]: %s', $rejection['key'], implode(' | ', $rejection['errors'])));
        }

        return self::SUCCESS;
    }
}
