import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as institutionsRoute } from '@/routes/admin/institutions';
import { index as programsRoute } from '@/routes/admin/programs';
import { index as reviewQueueRoute } from '@/routes/admin/review-queue';
import { index as sourcesRoute } from '@/routes/admin/sources';

type QueueEntry = {
    status: string;
    label: string;
    color: string;
    count: number;
};

type Stats = {
    institutions: number;
    programs: number;
    published_programs: number;
    sources: number;
};

export default function AdminDashboard({
    stats,
    queue,
}: {
    stats: Stats;
    queue: QueueEntry[];
}) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Admin', href: adminDashboard() }]}>
            <Head title="Admin dashboard" />
            <div className="min-h-screen p-4 md:p-6">
                <div className="grid auto-rows-min gap-4 md:grid-cols-4">
                    <StatCard label="Institutions" value={stats.institutions} />
                    <StatCard
                        label="Programs"
                        value={stats.programs}
                        hint={`${stats.published_programs} published`}
                    />
                    <StatCard label="Sources" value={stats.sources} />
                    <StatCard
                        label="Needs review"
                        value={
                            queue.find(
                                (entry) => entry.status === 'needs_review',
                            )?.count ?? 0
                        }
                        hint="records awaiting verification"
                        alert={
                            (queue.find(
                                (entry) => entry.status === 'needs_review',
                            )?.count ?? 0) > 0
                        }
                    />
                </div>

                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle>Review queue</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-3">
                        {queue.map((entry) => (
                            <Link
                                key={entry.status}
                                href={reviewQueueRoute({
                                    query: { status: entry.status },
                                })}
                                className="flex items-center gap-2 rounded-lg border p-3 transition-colors hover:bg-muted"
                            >
                                <Badge variant="outline">{entry.label}</Badge>
                                <span className="text-xl font-semibold tabular-nums">
                                    {entry.count}
                                </span>
                            </Link>
                        ))}
                    </CardContent>
                </Card>

                <div className="mt-6 flex flex-wrap gap-3 text-sm">
                    <Link
                        className="underline underline-offset-4"
                        href={institutionsRoute()}
                    >
                        Institutions
                    </Link>
                    <Link
                        className="underline underline-offset-4"
                        href={programsRoute()}
                    >
                        Programs
                    </Link>
                    <Link
                        className="underline underline-offset-4"
                        href={sourcesRoute()}
                    >
                        Sources
                    </Link>
                    <Link
                        className="underline underline-offset-4"
                        href={reviewQueueRoute()}
                    >
                        Review queue
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}

function StatCard({
    label,
    value,
    hint,
    alert = false,
}: {
    label: string;
    value: number;
    hint?: string;
    alert?: boolean;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {label}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    className={`text-3xl font-bold tabular-nums ${alert ? 'text-red-600 dark:text-red-400' : ''}`}
                >
                    {value}
                </div>
                {hint ? (
                    <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
                ) : null}
            </CardContent>
        </Card>
    );
}
