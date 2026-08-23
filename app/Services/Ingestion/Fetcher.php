<?php

namespace App\Services\Ingestion;

interface Fetcher
{
    /**
     * Returns the raw payload body for the given definition.
     */
    public function fetch(SourceDefinition $definition): string;
}
