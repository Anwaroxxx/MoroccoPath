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
        Schema::create('education_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            // ACADEMIC = school/university ladder, PROFESSIONAL-style rows live in qualifications.
            $table->string('category', 32)->default('academic');
            // Strict total order used for "minimum level" comparisons.
            $table->unsignedInteger('rank')->unique();
            // Years after the Bac this level is worth (null for pre-Bac levels).
            $table->unsignedSmallInteger('bac_plus_years')->nullable();
            $table->timestamps();
        });

        Schema::create('qualifications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            // The academic level a holder of this qualification is considered to have.
            $table->foreignId('equivalent_level_id')
                ->nullable()
                ->constrained('education_levels')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bac_branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->timestamps();
        });

        // Aliases let source data ("PC", "Sciences Physiques et Chimiques", ...)
        // map onto canonical branches without free-text comparisons.
        Schema::create('bac_branch_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bac_branch_id')->constrained()->cascadeOnDelete();
            $table->string('alias')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bac_branch_aliases');
        Schema::dropIfExists('bac_branches');
        Schema::dropIfExists('qualifications');
        Schema::dropIfExists('education_levels');
    }
};
