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
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained()->restrictOnDelete();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            // The specific campus offering the program; null = any campus of the institution.
            $table->foreignId('campus_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Education level achieved upon completion.
            $table->foreignId('education_level_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->unsignedSmallInteger('duration_months')->nullable();
            // Human-readable duration as published ("3 ans", "2 semestres").
            $table->string('duration_label')->nullable();
            // FULL_TIME, PART_TIME, EVENING, ONLINE, HYBRID, OTHER.
            $table->string('study_mode', 32)->default('full_time')->index();
            // fr, ar, en, mixed...
            $table->string('language', 16)->nullable();
            // DRAFT, PUBLISHED, ARCHIVED — only PUBLISHED is publicly visible.
            $table->string('status', 32)->default('draft')->index();
            $table->timestamps();

            $table->index(['specialty_id', 'status']);
            $table->index(['institution_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
        Schema::dropIfExists('specialties');
        Schema::dropIfExists('fields');
    }
};
