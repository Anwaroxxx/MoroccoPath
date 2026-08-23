import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, RefreshCw, XCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

type Recommendation = {
    program: {
        id: number;
        slug: string;
        name: string;
        study_mode: string;
        duration_label: string | null;
        institution_name: string | null;
        city: string | null;
        is_free: boolean;
    };
    match_score: number | null;
    eligible: boolean;
    reasons: string[];
    missing_requirements: string[];
    alternatives: Array<{ slug: string; name: string }>;
};

export default function Results({
    recommendations,
}: {
    recommendations: Recommendation[];
}) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Home', href: '/' },
                { title: 'My paths', href: '/results' },
            ]}
        >
            <Head title="Your paths" />
            <div className="min-h-screen p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Your paths
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Ranked for your situation. Programs you can&apos;t
                            access yet are shown too — with what&apos;s missing.
                        </p>
                    </div>
                    <Link
                        href="/orientation"
                        className="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm hover:bg-muted"
                    >
                        <RefreshCw className="size-4" /> Update my answers
                    </Link>
                </div>

                <div className="mt-6 grid gap-4 lg:grid-cols-2">
                    {recommendations.map((recommendation) => (
                        <Card
                            key={recommendation.program.id}
                            className="flex h-full flex-col"
                        >
                            <CardHeader className="flex-row items-start justify-between space-y-0">
                                <div>
                                    <CardTitle className="text-lg">
                                        {recommendation.program.name}
                                    </CardTitle>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {
                                            recommendation.program
                                                .institution_name
                                        }
                                        {recommendation.program.city
                                            ? ` · ${recommendation.program.city}`
                                            : ''}
                                    </p>
                                </div>
                                <MatchBadge
                                    score={recommendation.match_score}
                                />
                            </CardHeader>

                            <CardContent className="flex flex-1 flex-col gap-3">
                                <span
                                    className={`inline-flex w-fit items-center gap-1 text-sm font-medium ${recommendation.eligible ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}`}
                                >
                                    {recommendation.eligible ? (
                                        <>
                                            <CheckCircle2 className="size-4" />{' '}
                                            You can apply
                                        </>
                                    ) : (
                                        <>
                                            <XCircle className="size-4" /> Not
                                            currently eligible
                                        </>
                                    )}
                                </span>

                                {recommendation.reasons.length > 0 ? (
                                    <ul className="space-y-1 text-sm">
                                        {recommendation.reasons.map(
                                            (reason) => (
                                                <li
                                                    key={reason}
                                                    className="flex items-start gap-2"
                                                >
                                                    <span aria-hidden>•</span>
                                                    {reason}
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                ) : null}

                                {recommendation.missing_requirements.length >
                                0 ? (
                                    <div className="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-800 dark:bg-amber-950/40">
                                        <p className="mb-1 text-xs font-medium tracking-wide text-amber-700 uppercase dark:text-amber-400">
                                            What you would need
                                        </p>
                                        {recommendation.missing_requirements.map(
                                            (missing) => (
                                                <p key={missing}>• {missing}</p>
                                            ),
                                        )}
                                    </div>
                                ) : null}

                                <div className="mt-auto flex flex-wrap items-center justify-between gap-2 pt-2">
                                    <Link
                                        href={`/programs/${recommendation.program.slug}`}
                                        className="inline-flex items-center gap-1 text-sm font-medium underline underline-offset-4"
                                    >
                                        Details{' '}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                    {recommendation.alternatives.length > 0 ? (
                                        <div className="text-xs text-muted-foreground">
                                            Alternatives:{' '}
                                            {recommendation.alternatives.map(
                                                (alternative, i) => (
                                                    <span
                                                        key={alternative.slug}
                                                    >
                                                        {i > 0 ? ', ' : ''}
                                                        <Link
                                                            href={`/programs/${alternative.slug}`}
                                                            className="underline underline-offset-2"
                                                        >
                                                            {alternative.name}
                                                        </Link>
                                                    </span>
                                                ),
                                            )}
                                        </div>
                                    ) : null}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {recommendations.length === 0 ? (
                    <p className="mt-16 text-center text-muted-foreground">
                        No published programs to rank yet. Check back soon — new
                        opportunities are verified continuously.
                    </p>
                ) : null}
            </div>
        </AppLayout>
    );
}

function MatchBadge({ score }: { score: number | null }) {
    if (score === null) {
        return <Badge variant="outline">Not enough info</Badge>;
    }

    const tone =
        score >= 75
            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300'
            : score >= 50
              ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300'
              : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';

    return <Badge className={`${tone} tabular-nums`}>{score}% match</Badge>;
}
