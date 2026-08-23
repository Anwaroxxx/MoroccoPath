import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as institutionsRoute } from '@/routes/admin/institutions';

type LastReference = {
    source: string;
    status: string;
    academic_year: string | null;
    last_verified_at: string | null;
};

type Institution = {
    id: number;
    name: string;
    slug: string;
    type: string;
    status: string;
    campuses_count: number;
    programs_count: number;
    external_identifier: string | null;
    last_reference: LastReference | null;
};

export default function AdminInstitutions({
    institutions,
    filters,
}: {
    institutions: { data: Institution[]; links: unknown[] };
    filters: { q: string };
}) {
    const search = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const value = new FormData(event.currentTarget).get('q');
        window.location.href = `${institutionsRoute().url}?q=${encodeURIComponent(String(value ?? ''))}`;
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Admin', href: adminDashboard() },
                { title: 'Institutions', href: institutionsRoute() },
            ]}
        >
            <Head title="Institutions — Admin" />
            <div className="min-h-screen p-4 md:p-6">
                <form className="flex max-w-sm gap-2" onSubmit={search}>
                    <Input
                        name="q"
                        defaultValue={filters.q}
                        placeholder="Search institutions…"
                    />
                    <button
                        className="rounded-md border px-3 text-sm hover:bg-muted"
                        type="submit"
                    >
                        Search
                    </button>
                </form>

                <Card className="mt-4">
                    <CardContent className="p-0">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="p-3">Institution</th>
                                    <th className="p-3">Type</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3">Campuses</th>
                                    <th className="p-3">Programs</th>
                                    <th className="p-3">Last provenance</th>
                                </tr>
                            </thead>
                            <tbody>
                                {institutions.data.map((institution) => (
                                    <tr
                                        key={institution.id}
                                        className="border-b hover:bg-muted/50"
                                    >
                                        <td className="p-3">
                                            <span className="font-medium">
                                                {institution.name}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {institution.slug}
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            {institution.type}
                                        </td>
                                        <td className="p-3">
                                            {institution.status}
                                        </td>
                                        <td className="p-3 tabular-nums">
                                            {institution.campuses_count}
                                        </td>
                                        <td className="p-3 tabular-nums">
                                            {institution.programs_count}
                                        </td>
                                        <td className="p-3 text-xs">
                                            {institution.last_reference ? (
                                                <>
                                                    <Badge variant="outline">
                                                        {
                                                            institution
                                                                .last_reference
                                                                .status
                                                        }
                                                    </Badge>
                                                    <span className="ml-1">
                                                        {
                                                            institution
                                                                .last_reference
                                                                .source
                                                        }
                                                    </span>
                                                    {institution.last_reference
                                                        .academic_year ? (
                                                        <span className="block text-muted-foreground">
                                                            AY{' '}
                                                            {
                                                                institution
                                                                    .last_reference
                                                                    .academic_year
                                                            }
                                                        </span>
                                                    ) : null}
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
                        institutions.links as Array<{
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

export function Pagination({
    links,
}: {
    links: Array<{ url: string | null; label: string; active: boolean }>;
}) {
    return (
        <div className="mt-4 flex flex-wrap items-center gap-2 text-sm">
            {links.map((link, i) =>
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
    );
}
