<?php

namespace App\Services\Ingestion;

use Illuminate\Support\Str;

/**
 * Deterministic text normalization used for deduplication keys and slugs.
 * "Institut Spécialisé de Technologie Appliquée Casablanca" and
 * "ISTA Casa" never become duplicate institutions because of accents,
 * casing or punctuation.
 */
final class TextNormalizer
{
    public static function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    public static function translit(string $value): string
    {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $transliterated === false ? $value : $transliterated;
    }

    /**
     * Comparison key: lowercase, accent-free, punctuation stripped.
     */
    public static function key(string $value): string
    {
        $cleaned = self::translit(self::clean(mb_strtolower($value)));

        return (string) preg_replace('/[^a-z0-9]+/', '', $cleaned);
    }

    public static function slug(string $value): string
    {
        return Str::slug(self::translit(self::clean($value)));
    }
}
