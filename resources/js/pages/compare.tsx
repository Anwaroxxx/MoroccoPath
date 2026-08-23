import { Head, Link, router } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index as programsRoute } from '@/routes/programs';

type Row = {
    name: string;
    slug: string;
    institution: string;
    city: string | null;
    study_mode: string;
    duration_label: string | null;
    is_free: boolean;
    requirements: string[];
    careers: string[];
};

type CatalogEntry = { slug: string; name: string };

export default function Compare({
    selected,
    rows,
    catalog,
}: {
    selected: string[];
    rows: Row[];
    catalog: CatalogEntry[];
}) {
    const [query, setQuery] = useState('');
    const matches = catalog.filter(
        (entry) =>
            !selected.includes(entry.slug) &&
            entry.name.toLowerCase().includes(query.toLowerCase()),
    );

    const add = (slug: string) => {
        const next = [...selected, slug].slice(0, 3);
        router.visit(`/compare?programs=${next.join(',')}`);
    };

    const remove = (slug: string) => {
        const next = selected.filter((value) => value !== slug);
        router.visit(
            next.length > 0
                ? `/compare?programs=${next.join(',')}`
                : '/compare',
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Home', href: '/' },
                { title: 'Compare', href: '/compare' },
            ]}
        >
            <Head title="Compare programs" />
            <div className="min-h-screen p-4 md:p-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Compare programs
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Up to three published programs, side by side.
                </p>

                {/* Selected chips */}
                <div className="mt-4 flex flex-wrap gap-2">
                    {rows.map((row) => (
                        <Badge key={row.slug} className="gap-1 py-1 pr-1 pl-2">
                            {row.name}
                            <button
                                type="button"
                                aria-label={`Remove ${row.name}`}
                                onClick={() => remove(row.slug)}
                                className="rounded-full p-0.5 hover:bg-black/10 dark:hover:bg-white/20"
                            >
                                <X className="size-3" />
                            </button>
                        </Badge>
                    ))}
                    {selected.length < 3 ? (
                        <input
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder={
                                selected.length === 0
                                    ? 'Search a program to add…'
                                    : 'Add another…'
                            }
                            className="w-56 rounded-md border bg-background px-3 py-1 text-sm"
                        />
                    ) : null}
                </div>

                {/* Search results */}
                {query && matches.length > 0 ? (
                    <div className="mt-2 w-72 rounded-md border bg-background p-1 shadow-sm">
                        {matches.slice(0, 6).map((entry) => (
                            <button
                                key={entry.slug}
                                type="button"
                                onClick={() => {
                                    setQuery('');
                                    add(entry.slug);
                                }}
                                className="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-muted"
                            >
                                {entry.name}
                            </button>
                        ))}
                    </div>
                ) : null}

                {/* Comparison table */}
                {rows.length > 0 ? (
                    <Card className="mt-6 overflow-x-auto">
                        <CardContent className="p-0">
                            <table className="w-full min-w-[40rem] text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="w-40 p-3">&nbsp;</th>
                                        {rows.map((row) => (
                                            <th
                                                key={row.slug}
                                                className="p-3 font-semibold text-foreground"
                                            >
                                                {row.name}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    <CompareRow
                                        label="Institution"
                                        cells={rows.map(
                                            (row) => row.institution,
                                        )}
                                    />
                                    <CompareRow
                                        label="City"
                                        cells={rows.map(
                                            (row) => row.city ?? '—',
                                        )}
                                    />
                                    <CompareRow
                                        label="Mode"
                                        cells={rows.map(
                                            (row) => row.study_mode,
                                        )}
                                    />
                                    <CompareRow
                                        label="Duration"
                                        cells={rows.map(
                                            (row) => row.duration_label ?? '—',
                                        )}
                                    />
                                    <CompareRow
                                        label="Cost"
                                        cells={rows.map((row) =>
                                            row.is_free
                                                ? 'Free'
                                                : 'See details',
                                        )}
                                    />
                                    <CompareRow
                                        label="Who can apply"
                                        cells={rows.map((row) =>
                                            row.requirements.length > 0
                                                ? ''
                                                : 'Not yet verified',
                                        )}
                                        listCells={rows.map(
                                            (row) => row.requirements,
                                        )}
                                    />
                                    <CompareRow
                                        label="Leads to"
                                        cells={rows.map((row) =>
                                            row.careers.length > 0 ? '' : '—',
                                        )}
                                        listCells={rows.map(
                                            (row) => row.careers,
                                        )}
                                    />
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="mt-10 text-center text-muted-foreground">
                        <p>Add one or more programs above to compare them.</p>
                        <Link
                            href={programsRoute().url}
                            className="mt-2 inline-block underline underline-offset-4"
                        >
                            Browse all programs →
                        </Link>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function CompareRow({
    label,
    cells,
    listCells,
}: {
    label: string;
    cells: string[];
    listCells?: string[][];
}) {
    return (
        <tr className="border-b align-top">
            <td className="p-3 font-medium text-muted-foreground">{label}</td>
            {listCells
                ? listCells.map((items, i) => (
                      <td key={i} className="p-3">
                          {items.length > 0
                              ? items.map((item) => <p key={item}>• {item}</p>)
                              : cells[i] || '—'}
                      </td>
                  ))
                : cells.map((cell, i) => (
                      <td key={i} className="p-3">
                          {cell}
                      </td>
                  ))}
        </tr>
    );
}
