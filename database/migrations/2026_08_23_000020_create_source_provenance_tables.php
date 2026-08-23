<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // OFFICIAL_GOVERNMENT, OFFICIAL_INSTITUTION, ... (App\Enums\SourceType)
            $table->string('type', 64);
            // 1 = highest trust (government) ... 6 = other. Derived from type, stored for querying.
            $table->unsignedTinyInteger('trust_level');
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Provenance records: any important model can cite where its facts came from.
        Schema::create('source_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->morphs('referencable');
            $table->string('source_url')->nullable();
            $table->string('source_title')->nullable();
            $table->date('published_at')->nullable();
            $table->date('last_verified_at')->nullable();
            // Academic year this reference applies to, e.g. "2025/2026".
            $table->string('academic_year', 16)->nullable()->index();
            // VERIFIED, NEEDS_REVIEW, EXPIRED, CONFLICTING, UNKNOWN.
            $table->string('verification_status', 32)->default('unknown')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_id', 'referencable_type', 'referencable_id', 'academic_year'],
                'source_references_unique_citation',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_references');
        Schema::dropIfExists('sources');
    }
};
