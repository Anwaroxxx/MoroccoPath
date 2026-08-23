import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    Compass,
    GraduationCap,
    MapPinned,
    Route,
    Wallet,
} from 'lucide-react';
import { home, login, logout, register } from '@/routes';

const educationLevels = [
    'No Bac',
    'Bac level',
    'Bac',
    'Bac+1 · Bac+2',
    'Licence',
    'Master',
    'Engineer',
    'Doctorate',
];

const paths = [
    {
        title: 'University route',
        steps: ['Bac', 'Public university', 'Licence', 'Master', 'Career'],
    },
    {
        title: 'Coding school route',
        steps: ['Niveau Bac', '1337 / YouCode', 'Portfolio', 'Developer'],
    },
    {
        title: 'Vocational route',
        steps: [
            'No Bac',
            'OFPPT / ISTA',
            'Diploma + experience',
            'Skilled trade',
        ],
    },
];

const features = [
    {
        icon: Compass,
        title: 'Tell us where you are',
        body: 'Your education level, city, budget and interests — even "I don\u2019t know yet" works.',
    },
    {
        icon: Route,
        title: 'See every realistic path',
        body: 'Programs you can apply to — and the ones you can\u2019t yet, with what\u2019s missing and real alternatives.',
    },
    {
        icon: BadgeCheck,
        title: 'Verified sources only',
        body: 'Every requirement is tied to an official source with a verification status. Unverified data is flagged, never presented as fact.',
    },
];

export default function Home() {
    const { auth } = usePage().props;
    const isLoggedIn = auth.user !== null;

    return (
        <>
            <Head title="MoroccoPath — Find your path">
                <link rel="preconnect" href="https://fonts.googleapis.com" />
            </Head>

            <div className="min-h-screen bg-background text-foreground">
                <header className="mx-auto flex max-w-6xl items-center justify-between px-4 py-5 md:px-6">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 font-semibold tracking-tight"
                    >
                        <span className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                            <MapPinned className="size-5" />
                        </span>
                        MoroccoPath
                    </Link>
                    <nav className="flex items-center gap-2 text-sm">
                        {isLoggedIn ? (
                            <>
                                <Link
                                    href="/results"
                                    className="rounded-md px-3 py-2 hover:bg-muted"
                                >
                                    My paths
                                </Link>
                                <Link
                                    href="/orientation"
                                    className="rounded-md bg-primary px-3 py-2 font-medium text-primary-foreground hover:opacity-90"
                                >
                                    Update my profile
                                </Link>
                                <Link
                                    href={logout()}
                                    method="post"
                                    as="button"
                                    className="rounded-md px-3 py-2 hover:bg-muted"
                                >
                                    Log out
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="rounded-md px-3 py-2 hover:bg-muted"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={register()}
                                    className="rounded-md bg-primary px-3 py-2 font-medium text-primary-foreground hover:opacity-90"
                                >
                                    Get started
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                {/* Hero */}
                <section className="relative overflow-hidden border-b">
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 opacity-60"
                        style={{
                            background:
                                'radial-gradient(55rem 28rem at 85% -10%, color-mix(in oklab, var(--primary) 22%, transparent), transparent), radial-gradient(40rem 22rem at 5% 0%, color-mix(in oklab, var(--accent) 16%, transparent), transparent)',
                        }}
                    />
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 opacity-[0.05]"
                        style={{
                            backgroundImage:
                                'repeating-conic-gradient(from 45deg at 50% 50%, var(--primary) 0% 12.5%, transparent 12.5% 25%)',
                            backgroundSize: '56px 56px',
                            maskImage:
                                'linear-gradient(to bottom, black, transparent 70%)',
                            WebkitMaskImage:
                                'linear-gradient(to bottom, black, transparent 70%)',
                        }}
                    />
                    <div className="relative mx-auto grid max-w-6xl gap-10 px-4 py-16 md:grid-cols-[1.2fr_1fr] md:items-center md:px-6 md:py-24">
                        <div>
                            <p className="mb-3 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs text-muted-foreground">
                                <span className="size-2 rounded-full bg-emerald-500" />
                                Built for every level — no Bac to doctorate
                            </p>
                            <h1 className="text-4xl leading-tight font-bold tracking-tight text-balance md:text-6xl">
                                Every path you can actually take
                                in&nbsp;Morocco.
                            </h1>
                            <p className="mt-5 max-w-xl text-lg text-pretty text-muted-foreground">
                                Universities, OFPPT, coding schools, vocational
                                training. Tell us your situation — we map it to
                                the opportunities that fit, explain why each one
                                fits, and what to do when it doesn&apos;t.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center gap-3">
                                <Link
                                    href={isLoggedIn ? '/results' : register()}
                                    className="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-3 font-medium text-primary-foreground transition-opacity hover:opacity-90"
                                >
                                    {isLoggedIn
                                        ? 'See my paths'
                                        : 'Start exploring'}
                                    <ArrowRight className="size-4" />
                                </Link>
                                <a
                                    href="#how"
                                    className="rounded-md border px-5 py-3 hover:bg-muted"
                                >
                                    How it works
                                </a>
                            </div>
                        </div>

                        <div className="rounded-2xl border bg-card p-5 shadow-sm">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Example paths to
                            </p>
                            <p className="mb-4 text-xl font-semibold">
                                Software Developer
                            </p>
                            <div className="space-y-3">
                                {paths.map((path) => (
                                    <div
                                        key={path.title}
                                        className="rounded-xl border p-3"
                                    >
                                        <p className="text-sm font-medium">
                                            {path.title}
                                        </p>
                                        <div className="mt-2 flex flex-wrap items-center gap-1 text-xs text-muted-foreground">
                                            {path.steps.map((step, i) => (
                                                <span
                                                    key={`${path.title}-${step}`}
                                                    className="flex items-center gap-1"
                                                >
                                                    {i > 0 ? (
                                                        <ArrowRight className="size-3" />
                                                    ) : null}
                                                    {step}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <p className="mt-3 text-xs text-muted-foreground">
                                Multiple entry points. No single “right” path.
                            </p>
                        </div>
                    </div>
                </section>

                {/* Levels strip */}
                <section className="border-b bg-muted/40">
                    <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-center gap-2 px-4 py-6 md:px-6">
                        {educationLevels.map((level) => (
                            <span
                                key={level}
                                className="rounded-full border bg-background px-3 py-1 text-sm"
                            >
                                {level}
                            </span>
                        ))}
                    </div>
                </section>

                {/* How it works */}
                <section
                    id="how"
                    className="mx-auto max-w-6xl px-4 py-16 md:px-6 md:py-24"
                >
                    <h2 className="text-3xl font-bold tracking-tight">
                        How MoroccoPath works
                    </h2>
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        Not a school directory. A system that maps your
                        situation to your real options — and explains itself.
                    </p>
                    <div className="mt-10 grid gap-6 md:grid-cols-3">
                        {features.map((feature) => (
                            <div
                                key={feature.title}
                                className="rounded-2xl border p-6"
                            >
                                <feature.icon className="size-6 text-primary" />
                                <h3 className="mt-4 font-semibold">
                                    {feature.title}
                                </h3>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {feature.body}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Questions we answer */}
                <section className="border-y bg-muted/40">
                    <div className="mx-auto max-w-6xl px-4 py-16 md:px-6 md:py-20">
                        <h2 className="text-2xl font-bold tracking-tight md:text-3xl">
                            The questions you actually have
                        </h2>
                        <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {[
                                { icon: GraduationCap, q: 'Can I apply?' },
                                { icon: Wallet, q: 'What does it cost?' },
                                { icon: MapPinned, q: 'Where is it?' },
                                { icon: Route, q: 'What can I become?' },
                            ].map((item) => (
                                <div
                                    key={item.q}
                                    className="flex items-center gap-3 rounded-xl border bg-background p-4"
                                >
                                    <item.icon className="size-5 shrink-0 text-primary" />
                                    <span className="font-medium">
                                        {item.q}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA */}
                <section className="mx-auto max-w-6xl px-4 py-16 text-center md:px-6 md:py-24">
                    <h2 className="text-3xl font-bold tracking-tight">
                        Your situation is not a dead end.
                    </h2>
                    <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
                        Create a free account, answer a few questions, and see
                        the paths open to you today.
                    </p>
                    <Link
                        href={isLoggedIn ? '/results' : register()}
                        className="mt-8 inline-flex items-center gap-2 rounded-md bg-primary px-6 py-3 font-medium text-primary-foreground transition-opacity hover:opacity-90"
                    >
                        {isLoggedIn ? 'See my paths' : 'Create my free account'}
                        <ArrowRight className="size-4" />
                    </Link>
                </section>

                <footer className="border-t">
                    <div className="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between md:px-6">
                        <p>© {new Date().getFullYear()} MoroccoPath</p>
                        <p>
                            Where data is unverified it is labelled as such — we
                            never invent answers.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
