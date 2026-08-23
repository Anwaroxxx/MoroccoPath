import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as reviewQueueRoute } from '@/routes/admin/review-queue';
import { update as updateReferenceRoute } from '@/routes/admin/source-references';

type Reference = {
    id: number;
    record_type: string;
    record_name: string;
    source_name: string;
    source_trust: number;
    source_url: string | null;
    academic_year: string | null;
    last_verified_at: string | null;
    verification_status: string;
};

type Tab = { value: string; label: string };

const STATUS_COLORS: Record<string, string> = {
    verified:
        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    needs_review:
        'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    expired: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    conflicting: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    unknown: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
};

export default function ReviewQueue({
    status,
    tabs,
    references,
}: {
    status: string;
    tabs: Tab[];
    references: { data: Reference[]; links: unknown[] };
}) {
    const setStatus = (next: string) =>
        router.get(
            reviewQueueRoute().url,
            { status: next },
            { preserveState: true },
        );

    const changeStatus = (reference: Reference, next: string) => {
        router.patch(
            updateReferenceRoute({ source_reference: reference.id }).url,
            { verification_status: next },
            { preserveScroll: true },
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Admin', href: adminDashboard() },
                { title: 'Review queue', href: reviewQueueRoute() },
            ]}
        >
            <Head title="Review queue" />
            <div className="min-h-screen p-4 md:p-6">
                <div className="flex flex-wrap gap-2">
                    {tabs.map((tab) => (
                        <Button
                            key={tab.value}
                            variant={
                                tab.value === status ? 'default' : 'outline'
                            }
                            size="sm"
                            onClick={() => setStatus(tab.value)}
                        >
                            {tab.label}
                        </Button>
                    ))}
                </div>

                <Card className="mt-4">
                    <CardContent className="p-0">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="p-3">Record</th>
                                    <th className="p-3">Source (trust)</th>
                                    <th className="p-3">Academic year</th>
                                    <th className="p-3">Last verified</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {references.data.map((reference) => (
                                    <tr
                                        key={reference.id}
                                        className="border-b align-top hover:bg-muted/50"
                                    >
                                        <td className="p-3">
                                            <span className="font-medium">
                                                {reference.record_name}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {reference.record_type}
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            {reference.source_name}
                                            <span className="ml-1 text-xs text-muted-foreground">
                                                (T{reference.source_trust})
                                            </span>
                                            {reference.source_url ? (
                                                <a
                                                    href={reference.source_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="block text-xs underline underline-offset-2"
                                                >
                                                    open source ↗
                                                </a>
                                            ) : null}
                                        </td>
                                        <td className="p-3 tabular-nums">
                                            {reference.academic_year ?? '—'}
                                        </td>
                                        <td className="p-3 tabular-nums">
                                            {reference.last_verified_at ??
                                                'never'}
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                className={
                                                    STATUS_COLORS[
                                                        reference
                                                            .verification_status
                                                    ] ?? ''
                                                }
                                            >
                                                {reference.verification_status}
                                            </Badge>
                                        </td>
                                        <td className="p-3">
                                            <div className="flex flex-wrap gap-1">
                                                {reference.verification_status !==
                                                'verified' ? (
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            changeStatus(
                                                                reference,
                                                                'verified',
                                                            )
                                                        }
                                                    >
                                                        Verify
                                                    </Button>
                                                ) : null}
                                                {reference.verification_status !==
                                                'conflicting' ? (
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        onClick={() =>
                                                            changeStatus(
                                                                reference,
                                                                'conflicting',
                                                            )
                                                        }
                                                    >
                                                        Flag conflict
                                                    </Button>
                                                ) : null}
                                                {reference.verification_status !==
                                                'needs_review' ? (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            changeStatus(
                                                                reference,
                                                                'needs_review',
                                                            )
                                                        }
                                                    >
                                                        Reset
                                                    </Button>
                                                ) : null}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {references.data.length === 0 ? (
                                    <tr>
                                        <td
                                            className="p-6 text-center text-muted-foreground"
                                            colSpan={6}
                                        >
                                            Nothing in this queue.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <div className="mt-4 flex flex-wrap items-center gap-2 text-sm">
                    {(
                        references.links as Array<{
                            url: string | null;
                            label: string;
                            active: boolean;
                        }>
                    ).map((link, i) =>
                        link.url ? (
                            <Link
                                key={i}
                                href={link.url}
                                className={`rounded px-2 py-1 ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ) : (
                            <span
                                key={i}
                                className="rounded px-2 py-1 text-muted-foreground"
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ),
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
