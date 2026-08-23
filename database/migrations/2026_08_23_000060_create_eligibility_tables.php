<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rule semantics (evaluated by App\Services\Education\EligibilityEngine):
     *  - One row is one atomic condition.
     *  - The row's pivot values (levels / qualifications / branches) are alternatives: ANY match passes.
     *  - Rows sharing the same logic_group are AND-ed together.
     *  - Distinct logic_groups are OR-ed: at least one group must fully pass.
     *  - negate = true inverts a single condition (NOT).
     */
    public function up(): void
    {
        Schema::create('eligibility_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            // EDUCATION_LEVEL_MIN, EDUCATION_LEVEL_ANY_OF, QUALIFICATION_ANY_OF,
            // BAC_BRANCH_ANY_OF, MAX_AGE, MIN_GRADE, ENTRANCE_EXAM, INTERVIEW,
            // COMPETITION, OTHER (App\Enums\EligibilityConditionType).
            $table->string('condition_type', 64)->index();
            $table->boolean('negate')->default(false);
            $table->string('logic_group', 64)->default('default')->index();
            // Condition parameters, e.g. {"max_age": 30} or {"min_grade": 12}.
            $table->json('parameters')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['program_id', 'logic_group']);
        });

        Schema::create('eligibility_rule_education_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eligibility_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('education_level_id')->constrained()->restrictOnDelete();

            $table->unique(['eligibility_rule_id', 'education_level_id']);
        });

        Schema::create('eligibility_rule_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eligibility_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qualification_id')->constrained()->restrictOnDelete();

            $table->unique(['eligibility_rule_id', 'qualification_id']);
        });

        Schema::create('eligibility_rule_bac_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eligibility_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bac_branch_id')->constrained()->restrictOnDelete();

            $table->unique(['eligibility_rule_id', 'bac_branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eligibility_rule_bac_branches');
        Schema::dropIfExists('eligibility_rule_qualifications');
        Schema::dropIfExists('eligibility_rule_education_levels');
        Schema::dropIfExists('eligibility_rules');
    }
};
