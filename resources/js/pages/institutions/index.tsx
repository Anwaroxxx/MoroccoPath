import { Head, Link, router } from '@inertiajs/react';
import { MapPinned } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import PageHeader from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { home } from '@/routes';

type Campus = {
    name: string;
    city: string;
    region: string;
    address: string | null;
};

type Institution = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    website: string | null;
    campuses: Campus[];
    programs_count: number;
};

export default function InstitutionsIndex({
    institutions,
    filters,
}: {
    institutions: Institution[];
    filters: { q: string; city: string };
}) {
    const search = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const params = new URLSearchParams();

        for (const key of ['q', 'city']) {
            const value = String(data.get(key) ?? '').trim();

            if (value) {
                params.set(key, value);
            }
        }

        router.get(
            params.toString() ? `/institutions?${params}` : '/institutions',
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Home', href: home() },
                { title: 'Institutions', href: '/institutions' },
            ]}
        >
            <Head title="Institutions" />
            <div className="min-h-screen p-4 md:p-6">
                <PageHeader
                    title="Institutions"
                    subtitle="Verified organizations with published programs — universities, OFPPT centers, coding schools and more."
                />

                <form className="mt-5 flex max-w-xl gap-2" onSubmit={search}>
                    <Input
                        name="q"
                        defaultValue={filters.q}
                        placeholder="Search…"
                        className="max-w-xs"
                    />
                    <Input
                        name="city"
                        defaultValue={filters.city}
                        placeholder="City"
                        className="max-w-[10rem]"
                    />
                    <button
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                        type="submit"
                    >
                        Filter
                    </button>
                </form>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {institutions.map((institution) => (
                        <Link
                            key={institution.id}
                            href={`/institutions/${institution.slug}`}
                        >
                            <Card className="h-full transition-all hover:-translate-y-0.5 hover:shadow-md">
                                <CardContent className="p-5">
                                    <h2 className="font-semibold">
                                        {institution.name}
                                    </h2>
                                    <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                        {institution.description ??
                                            'No description yet.'}
                                    </p>
                                    <div className="mt-3 flex flex-wrap gap-1 text-xs">
                                        <Badge variant="outline">
                                            {institution.programs_count}{' '}
                                            programs
                                        </Badge>
                                        {[
                                            ...new Set(
                                                institution.campuses.map(
                                                    (campus) => campus.city,
                                                ),
                                            ),
                                        ]
                                            .slice(0, 3)
                                            .map((city) => (
                                                <Badge
                                                    key={city}
                                                    variant="outline"
                                                >
                                                    {city}
                                                </Badge>
                                            ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                {institutions.length === 0 ? (
                    <div className="mt-16 flex flex-col items-center gap-3 text-center text-muted-foreground">
                        <MapPinned className="size-10" />
                        <p>No institutions match yet.</p>
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}
