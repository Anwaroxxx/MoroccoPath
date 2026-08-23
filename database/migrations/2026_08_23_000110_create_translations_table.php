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
        // Canonical-data translations (spec §27): translated names live
        // here, never inside the business tables. One row per field+locale.
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('locale', 5)->index();
            $table->string('field', 64);
            $table->text('value');
            $table->timestamps();

            $table->unique(
                ['translatable_type', 'translatable_id', 'locale', 'field'],
                'translations_unique_field_locale',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
