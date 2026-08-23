import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    index as programsRoute,
    status as programStatusRoute,
} from '@/routes/admin/programs';
import { Pagination } from '@/pages/admin/institutions';

type LastReference = {
    source: string;
    status: string;
    academic_year: string | null;
    last_verified_at: string | null;
};

type Program = {
    id: number;
    name: string;
    slug: string;
    status: string;
    study_mode: string;
    duration_label: string | null;
    institution: string;
    city: string | null;
    academic_year: string | null;
    version_status: string | null;
    last_reference: LastReference | null;
};

type StatusTab = { value: string; label: string };

export default function AdminPrograms({
    programs,
    filters,
    statuses,
}: {
    programs: { data: Program[]; links: unknown[] };
    filters: { q: string; verification: string | null };
    statuses: StatusTab[];
}) {
    const filterHref = (status: string | null) => {
        const params = new URLSearchParams();

        if (filters.q) {
            params.set('q', filters.q);
        }

        if (status) {
            params.set('verification', status);
        }

        const query = params.toString();

        return query ? `${programsRoute().url}?${query}` : programsRoute().url;
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Admin', href: adminDashboard() },
                { title: 'Programs', href: programsRoute() },
            ]}
        >
            <Head title="Programs — Admin" />
            <div className="min-h-screen p-4 md:p-6">
                <div className="flex flex-wrap gap-2">
                    <Link href={filterHref(null)}>
                        <Badge
                            variant={
                                filters.verification === null
                                    ? 'default'
                                    : 'outline'
                            }
                        >
                            All
                        </Badge>
                    </Link>
                    {statuses.map((tab) => (
                        <Link key={tab.value} href={filterHref(tab.value)}>
                            <Badge
                                variant={
                                    filters.verification === tab.value
                                        ? 'default'
                                        : 'outline'
                                }
                            >
                                {tab.label}
                            </Badge>
                        </Link>
                    ))}
                </div>

                <Card className="mt-4">
                    <CardContent className="p-0">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="p-3">Program</th>
                                    <th className="p-3">Institution</th>
                                    <th className="p-3">City</th>
                                    <th className="p-3">Mode / duration</th>
                                    <th className="p-3">Version</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3">Last provenance</th>
                                </tr>
                            </thead>
                            <tbody>
                                {programs.data.map((program) => (
                                    <tr
                                        key={program.id}
                                        className="border-b hover:bg-muted/50"
                                    >
                                        <td className="p-3">
                                            <span className="font-medium">
                                                {program.name}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {program.slug}
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            {program.institution}
                                        </td>
                                        <td className="p-3">
                                            {program.city ?? '—'}
                                        </td>
                                        <td className="p-3 text-xs">
                                            {program.study_mode}
                                            {program.duration_label
                                                ? ` · ${program.duration_label}`
                                                : ''}
                                        </td>
                                        <td className="p-3 tabular-nums">
                                            {program.academic_year ?? '—'}
                                            {program.version_status ? (
                                                <span className="block text-xs text-muted-foreground">
                                                    {program.version_status}
                                                </span>
                                            ) : null}
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                variant={
                                                    program.status ===
                                                    'published'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {program.status}
                                            </Badge>
                                            <Button
                                                size="sm"
                                                variant={
                                                    program.status ===
                                                    'published'
                                                        ? 'outline'
                                                        : 'default'
                                                }
                                                className="ml-2"
                                                onClick={() =>
                                                    router.patch(
                                                        programStatusRoute({
                                                            program: program.id,
                                                        }).url,
                                                        {
                                                            status:
                                                                program.status ===
                                                                'published'
                                                                    ? 'draft'
                                                                    : 'published',
                                                        },
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                {program.status === 'published'
                                                    ? 'Unpublish'
                                                    : 'Publish'}
                                            </Button>
                                        </td>
                                        <td className="p-3 text-xs">
                                            {program.last_reference ? (
                                                <>
                                                    <Badge variant="outline">
                                                        {
                                                            program
                                                                .last_reference
                                                                .status
                                                        }
                                                    </Badge>
                                                    <span className="ml-1">
                                                        {
                                                            program
                                                                .last_reference
                                                                .source
                                                        }
                                                    </span>
                                                    {program.last_reference
                                                        .last_verified_at ? (
                                                        <span className="block text-muted-foreground">
                                                            verified{' '}
                                                            {
                                                                program
                                                                    .last_reference
                                                                    .last_verified_at
                                                            }
                                                        </span>
                                                    ) : (
                                                        <span className="block text-muted-foreground">
                                                            never verified
                                                        </span>
                                                    )}
                                                </>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    none
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Pagination
                    links={
                        programs.links as Array<{
                            url: string | null;
                            label: string;
                            active: boolean;
                        }>
                    }
                />
            </div>
        </AppLayout>
    );
}
