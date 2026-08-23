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
        // Academic-year snapshots: historical facts are never overwritten.
        Schema::create('program_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 16);
            $table->unsignedSmallInteger('duration_months')->nullable();
            // ACTIVE, EXPIRED, UPCOMING.
            $table->string('status', 32)->default('active');
            $table->text('admission_information')->nullable();
            $table->date('application_start')->nullable();
            $table->date('application_end')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'academic_year']);
        });

        Schema::create('admission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            // DOSSIER, CONCOURS, ENTRANCE_EXAM, INTERVIEW, OTHER.
            $table->string('type', 32)->default('other');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('application_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_version_id')->constrained()->cascadeOnDelete();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->string('intake_label')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 16)->nullable()->index();
            // REGISTRATION, TUITION_ANNUAL, TUITION_MONTHLY, MATERIALS, OTHER.
            $table->string('cost_type', 32);
            $table->decimal('amount_min', 10, 2)->nullable();
            $table->decimal('amount_max', 10, 2)->nullable();
            $table->string('currency', 3)->default('MAD');
            $table->boolean('is_free')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['program_id', 'cost_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('costs');
        Schema::dropIfExists('application_periods');
        Schema::dropIfExists('admission_rules');
        Schema::dropIfExists('program_versions');
    }
};
