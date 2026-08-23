import { LOCALES, useI18n } from '@/lib/i18n';

/**
 * Compact EN/FR/AR switcher for public pages. Persists to localStorage;
 * the provider mirrors the document direction for Arabic.
 */
export default function LanguageSwitcher() {
    const { locale, setLocale } = useI18n();

    return (
        <div
            className="inline-flex items-center rounded-full border p-0.5 text-xs"
            role="group"
            aria-label="Language"
        >
            {LOCALES.map((entry) => (
                <button
                    key={entry.value}
                    type="button"
                    onClick={() => setLocale(entry.value)}
                    className={`rounded-full px-2 py-1 transition-colors ${
                        locale === entry.value
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted'
                    }`}
                >
                    {entry.label}
                </button>
            ))}
        </div>
    );
}
