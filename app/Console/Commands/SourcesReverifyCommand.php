<?php

namespace App\Console\Commands;

use App\Services\Ingestion\SourceVerificationService;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;

class SourcesReverifyCommand extends Command
{
    protected $signature = 'sources:reverify
                            {--days=180 : Staleness window in days before a VERIFIED citation expires}';

    protected $description = 'Expire VERIFIED source references older than the staleness window';

    public function handle(SourceVerificationService $service): int
    {
        $days = max(1, (int) $this->option('days'));
        $service = new SourceVerificationService($days);

        ['expired' => $expired] = $service->refresh(now());

        info(sprintf('Expired %d stale verified reference(s) (window: %d days).', $expired, $days));

        return self::SUCCESS;
    }
}
