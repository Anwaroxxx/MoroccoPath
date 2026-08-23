import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, MapPin } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

type Campus = {
    name: string;
    city: string;
    region: string;
    address: string | null;
};

type InstitutionProgram = {
    slug: string;
    name: string;
    duration_label: string | null;
    study_mode: string;
};

type Institution = {
    name: string;
    slug: string;
    description: string | null;
    website: string | null;
    campuses: Campus[];
    programs: InstitutionProgram[];
};

export default function InstitutionShow({
    institution,
}: {
    institution: Institution;
}) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Home', href: '/' },
                { title: 'Institutions', href: '/institutions' },
                {
                    title: institution.name,
                    href: `/institutions/${institution.slug}`,
                },
            ]}
        >
            <Head title={institution.name} />
            <div className="min-h-screen p-4 md:p-6">
                <Link
                    href="/institutions"
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All institutions
                </Link>

                <header className="mt-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            {institution.name}
                        </h1>
                        {institution.description ? (
                            <p className="mt-2 max-w-2xl text-muted-foreground">
                                {institution.description}
                            </p>
                        ) : null}
                    </div>
                    {institution.website ? (
                        <a
                            href={institution.website}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm hover:bg-muted"
                        >
                            Official website <ExternalLink className="size-4" />
                        </a>
                    ) : null}
                </header>

                <div className="mt-8 grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <MapPin className="size-5 text-primary" />{' '}
                                Campuses
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {institution.campuses.length > 0 ? (
                                institution.campuses.map((campus) => (
                                    <div
                                        key={campus.name}
                                        className="rounded-lg border p-3"
                                    >
                                        <p className="font-medium">
                                            {campus.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {campus.city} · {campus.region}
                                        </p>
                                        {campus.address ? (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {campus.address}
                                            </p>
                                        ) : null}
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No campus data recorded yet.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Published programs
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {institution.programs.length > 0 ? (
                                institution.programs.map((program) => (
                                    <Link
                                        key={program.slug}
                                        href={`/programs/${program.slug}`}
                                        className="block rounded-lg border p-3 transition-colors hover:bg-muted"
                                    >
                                        <p className="font-medium">
                                            {program.name}
                                        </p>
                                        <div className="mt-1 flex gap-1 text-xs">
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
                                    </Link>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No published programs yet.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
