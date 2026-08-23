import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';

export type Locale = 'en' | 'fr' | 'ar';

export const LOCALES: Array<{ value: Locale; label: string }> = [
    { value: 'en', label: 'EN' },
    { value: 'fr', label: 'FR' },
    { value: 'ar', label: 'AR' },
];

type Dict = Record<string, string>;

const en: Dict = {
    'nav.programs': 'Programs',
    'nav.institutions': 'Institutions',
    'nav.paths': 'Career paths',
    'nav.compare': 'Compare',
    'nav.login': 'Log in',
    'nav.register': 'Get started',
    'nav.myPaths': 'My paths',
    'nav.updateProfile': 'Update my profile',
    'nav.logout': 'Log out',
    'hero.badge': 'Built for every level — no Bac to doctorate',
    'hero.title': 'Every path you can actually take in Morocco.',
    'hero.subtitle':
        'Universities, OFPPT, coding schools, vocational training. Tell us your situation — we map it to the opportunities that fit, explain why each one fits, and what to do when it doesn’t.',
    'hero.cta': 'Start exploring',
    'hero.cta.mine': 'See my paths',
    'hero.howItWorks': 'How it works',
    'programs.title': 'Programs',
    'programs.subtitle':
        'Published opportunities only — every record here has been reviewed against its official source.',
    'institutions.title': 'Institutions',
    'institutions.subtitle':
        'Verified organizations with published programs — universities, OFPPT centers, coding schools and more.',
    'paths.title': 'Career paths',
    'paths.subtitle':
        'Multiple routes can lead to the same career — explore the steps of each one, in any direction.',
    'common.search': 'Search…',
    'common.filter': 'Filter',
    'common.city': 'City',
};

const fr: Dict = {
    'nav.programs': 'Formations',
    'nav.institutions': 'Établissements',
    'nav.paths': 'Parcours',
    'nav.compare': 'Comparer',
    'nav.login': 'Connexion',
    'nav.register': 'Commencer',
    'nav.myPaths': 'Mes parcours',
    'nav.updateProfile': 'Modifier mon profil',
    'nav.logout': 'Déconnexion',
    'hero.badge': 'Pour tous les niveaux — du sans-Bac au doctorat',
    'hero.title': 'Toutes les voies réellement ouvertes au Maroc.',
    'hero.subtitle':
        'Universités, OFPPT, écoles de code, formation professionnelle. Décrivez votre situation — nous la relions aux opportunités qui correspondent, avec les explications, et les alternatives sinon.',
    'hero.cta': 'Explorer',
    'hero.cta.mine': 'Mes parcours',
    'hero.howItWorks': 'Comment ça marche',
    'programs.title': 'Formations',
    'programs.subtitle':
        'Uniquement des formations publiées — chaque fiche est vérifiée auprès de sa source officielle.',
    'institutions.title': 'Établissements',
    'institutions.subtitle':
        'Des établissements vérifiés avec des formations publiées — universités, centres OFPPT, écoles de code et plus.',
    'paths.title': 'Parcours métiers',
    'paths.subtitle':
        'Plusieurs routes peuvent mener au même métier — explorez les étapes de chacune, dans tous les sens.',
    'common.search': 'Rechercher…',
    'common.filter': 'Filtrer',
    'common.city': 'Ville',
};

const ar: Dict = {
    'nav.programs': 'البرامج',
    'nav.institutions': 'المؤسسات',
    'nav.paths': 'المسارات المهنية',
    'nav.compare': 'مقارنة',
    'nav.login': 'تسجيل الدخول',
    'nav.register': 'ابدأ الآن',
    'nav.myPaths': 'مساراتي',
    'nav.updateProfile': 'تحديث ملفي',
    'nav.logout': 'تسجيل الخروج',
    'hero.badge': 'لكل المستويات — من غير الباك إلى الدكتوراه',
    'hero.title': 'كل الطرق الممكنة فعلاً في المغرب.',
    'hero.subtitle':
        'الجامعات، التكوين المهني، مدارس البرمجة، التكوين الحرفي. أخبرنا عن وضعك — نربطه بالفرص المناسبة مع شرح السبب، والبدائل إن لم تكن مؤهلاً بعد.',
    'hero.cta': 'استكشف',
    'hero.cta.mine': 'مساراتي',
    'hero.howItWorks': 'كيف يعمل',
    'programs.title': 'البرامج',
    'programs.subtitle':
        'البرامج المنشورة فقط — تمت مراجعة كل بطاقة مقابل مصدرها الرسمي.',
    'institutions.title': 'المؤسسات',
    'institutions.subtitle':
        'مؤسسات موثوقة ببرامج منشورة — جامعات ومراكز التكوين المهني ومدارس البرمجة والمزيد.',
    'paths.title': 'المسارات المهنية',
    'paths.subtitle':
        'طرق متعددة قد تؤدي إلى نفس المهنة — استكشف خطوات كل مسار في أي اتجاه.',
    'common.search': 'بحث…',
    'common.filter': 'تصفية',
    'common.city': 'المدينة',
};

const DICTS: Record<Locale, Dict> = { en, fr, ar };

type LocaleContextValue = {
    locale: Locale;
    setLocale: (locale: Locale) => void;
    t: (key: string) => string;
};

const LocaleContext = createContext<LocaleContextValue>({
    locale: 'en',
    setLocale: () => {},
    t: (key) => key,
});

const STORAGE_KEY = 'locale';

function resolveInitialLocale(): Locale {
    if (typeof window === 'undefined') return 'en';
    const stored = window.localStorage.getItem(STORAGE_KEY);
    return stored === 'fr' || stored === 'ar' || stored === 'en'
        ? stored
        : 'en';
}

export function LocaleProvider({ children }: { children: ReactNode }) {
    const [locale, setLocaleState] = useState<Locale>(resolveInitialLocale);

    useEffect(() => {
        document.documentElement.lang = locale;
        // Arabic reads right-to-left; layout must mirror.
        document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
        window.localStorage.setItem(STORAGE_KEY, locale);
    }, [locale]);

    const value = useMemo<LocaleContextValue>(
        () => ({
            locale,
            setLocale: setLocaleState,
            t: (key: string) => DICTS[locale][key] ?? DICTS.en[key] ?? key,
        }),
        [locale],
    );

    return (
        <LocaleContext.Provider value={value}>
            {children}
        </LocaleContext.Provider>
    );
}

export function useI18n(): LocaleContextValue {
    return useContext(LocaleContext);
}
