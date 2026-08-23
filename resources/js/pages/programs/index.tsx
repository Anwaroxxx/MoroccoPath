import { Head, Link, router } from '@inertiajs/react';
import { MapPinned } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import PageHeader from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { home } from '@/routes';

type Program = {
    id: number;
    name: string;
    slug: string;
    study_mode: string;
    duration_label: string | null;
    institution_name: string;
    city: string | null;
    interests: Array<{ code: string; name: string }>;
};

type Paginated = {
    data: Program[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function ProgramsIndex({
    programs,
    filters,
}: {
    programs: Paginated;
    filters: { q: string; city: string; mode: string; interest: string };
}) {
    const search = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const params = new URLSearchParams();

        for (const key of ['q', 'city', 'mode', 'interest']) {
            const value = String(data.get(key) ?? '').trim();

            if (value) {
                params.set(key, value);
            }
        }

        router.get(params.toString() ? `/programs?${params}` : '/programs');
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Home', href: home() },
                { title: 'Programs', href: '/programs' },
            ]}
        >
            <Head title="Programs" />
            <div className="min-h-screen p-4 md:p-6">
                <PageHeader
                    title="Programs"
                    subtitle="Published opportunities only — every record here has been reviewed against its official source."
                />

                <form
                    className="mt-5 flex max-w-3xl flex-wrap gap-2"
                    onSubmit={search}
                >
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
                    <select
                        name="mode"
                        defaultValue={filters.mode}
                        className="rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">Any mode</option>
                        <option value="full_time">Full time</option>
                        <option value="part_time">Part time</option>
                        <option value="evening">Evening</option>
                        <option value="online">Online</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                    <button
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                        type="submit"
                    >
                        Filter
                    </button>
                </form>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {programs.data.map((program) => (
                        <Link
                            key={program.id}
                            href={`/programs/${program.slug}`}
                        >
                            <Card className="h-full transition-all hover:-translate-y-0.5 hover:shadow-md">
                                <CardContent className="p-5">
                                    <h2 className="font-semibold">
                                        {program.name}
                                    </h2>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {program.institution_name}
                                    </p>
                                    <div className="mt-3 flex flex-wrap gap-1 text-xs">
                                        {program.city ? (
                                            <Badge variant="outline">
                                                {program.city}
                                            </Badge>
                                        ) : null}
                                        <Badge variant="outline">
                                            {program.study_mode.replace(
                                                '_',
                                                ' ',
                                            )}
                                        </Badge>
                                        {program.duration_label ? (
                                            <Badge variant="outline">
                                                {program.duration_label}
                                            </Badge>
                                        ) : null}
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                {programs.data.length === 0 ? (
                    <div className="mt-16 flex flex-col items-center gap-3 text-center text-muted-foreground">
                        <MapPinned className="size-10" />
                        <p>
                            No published programs match yet. New opportunities
                            are added as they are verified.
                        </p>
                    </div>
                ) : null}

                <div className="mt-6 flex flex-wrap items-center gap-2 text-sm">
                    {programs.links.map((link, i) =>
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
