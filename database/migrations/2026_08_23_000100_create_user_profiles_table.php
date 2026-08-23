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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('education_level_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('qualification_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            // Canonical codes referencing taxonomy tables (spec §7: aliases
            // are resolved before storage, free text is never compared).
            $table->json('bac_branch_codes')->nullable();
            $table->json('interest_codes')->nullable();
            $table->json('skill_codes')->nullable();
            $table->json('career_goal_codes')->nullable();
            $table->decimal('bac_grade', 4, 2)->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->boolean('willing_to_relocate')->default(false);
            $table->string('preferred_study_mode', 32)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
