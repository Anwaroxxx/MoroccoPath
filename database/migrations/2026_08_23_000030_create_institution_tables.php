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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->string('region')->index();
            $table->timestamps();

            $table->unique(['city', 'region']);
        });

        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Canonical display name used after deduplication ("ISTA Casa" -> canonical).
            $table->string('canonical_name');
            $table->string('slug')->unique();
            // PUBLIC_UNIVERSITY, OFPPT, CODING_SCHOOL, ... (App\Enums\InstitutionType)
            $table->string('type', 64)->index();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            // ACTIVE, INACTIVE, UNKNOWN.
            $table->string('status', 32)->default('unknown')->index();
            // External registry identifier to keep deduplicated entities unique.
            $table->string('external_identifier')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('institution_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('alias')->unique();
            $table->timestamps();
        });

        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('name');
            // Denormalized for fast filtering; location_id is the normalized link.
            $table->string('city')->index();
            $table->string('region')->index();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campuses');
        Schema::dropIfExists('institution_aliases');
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('locations');
    }
};
