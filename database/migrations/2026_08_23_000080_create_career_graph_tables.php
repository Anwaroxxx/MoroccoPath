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
        Schema::create('career_paths', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('field_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('target_career_id')
                ->nullable()
                ->constrained('careers')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('career_path_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_path_id')->constrained()->cascadeOnDelete();
            // Self-reference allows branching paths (multiple entry points).
            $table->foreignId('parent_step_id')
                ->nullable()
                ->constrained('career_path_steps')
                ->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('education_level_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('program_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['career_path_id', 'parent_step_id']);
        });

        // "If you cannot access program X, consider program Y."
        Schema::create('alternative_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alternative_program_id')->constrained()->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['program_id', 'alternative_program_id']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('favoritable');
            $table->timestamps();

            $table->unique(['user_id', 'favoritable_type', 'favoritable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('alternative_paths');
        Schema::dropIfExists('career_path_steps');
        Schema::dropIfExists('career_paths');
    }
};
