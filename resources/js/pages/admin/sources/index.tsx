import { Head } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as sourcesRoute } from '@/routes/admin/sources';

type Source = {
    id: number;
    name: string;
    slug: string;
    type_label: string;
    trust_level: number;
    website: string | null;
    references_count: number;
};

export default function AdminSources({
    sources,
}: {
    sources: { data: Source[]; links: unknown[] };
}) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Admin', href: adminDashboard() },
                { title: 'Sources', href: sourcesRoute() },
            ]}
        >
            <Head title="Sources — Admin" />
            <div className="min-h-screen p-4 md:p-6">
                <Card>
                    <CardContent className="p-0">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="p-3">Source</th>
                                    <th className="p-3">Type</th>
                                    <th className="p-3">Trust</th>
                                    <th className="p-3">Citations</th>
                                    <th className="p-3">Website</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sources.data.map((source) => (
                                    <tr
                                        key={source.id}
                                        className="border-b hover:bg-muted/50"
                                    >
                                        <td className="p-3">
                                            <span className="font-medium">
                                                {source.name}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {source.slug}
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            {source.type_label}
                                        </td>
                                        <td className="p-3 tabular-nums">
                                            T{source.trust_level}
                                        </td>
                                        <td className="p-3 tabular-nums">
                                            {source.references_count}
                                        </td>
                                        <td className="p-3">
                                            {source.website ? (
                                                <a
                                                    className="underline underline-offset-2"
                                                    href={source.website}
                                                    rel="noopener noreferrer"
                                                    target="_blank"
                                                >
                                                    open ↗
                                                </a>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <div className="mt-4 text-xs text-muted-foreground">
                    Trust hierarchy (spec §4): T1 government · T2 institution ·
                    T3 official document · T4 open data · T5 secondary source ·
                    T6 other.
                </div>
            </div>
        </AppLayout>
    );
}
