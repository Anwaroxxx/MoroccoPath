import { Head, Link } from '@inertiajs/react';
import { MapPinned, Route } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { home } from '@/routes';

type Path = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    field: string | null;
    target_career: string | null;
    steps_count: number;
};

export default function PathsIndex({ paths }: { paths: Path[] }) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Home', href: home() },
                { title: 'Paths', href: '/paths' },
            ]}
        >
            <Head title="Career paths" />
            <div className="min-h-screen p-4 md:p-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Career paths
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Multiple routes can lead to the same career. Pick a path to
                    see its steps.
                </p>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {paths.map((path) => (
                        <Link key={path.id} href={`/paths/${path.slug}`}>
                            <Card className="h-full transition-shadow hover:shadow-md">
                                <CardContent className="p-5">
                                    <Route className="size-5 text-primary" />
                                    <h2 className="mt-3 font-semibold">
                                        {path.name}
                                    </h2>
                                    {path.description ? (
                                        <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                            {path.description}
                                        </p>
                                    ) : null}
                                    <div className="mt-3 flex flex-wrap gap-1 text-xs">
                                        <Badge variant="outline">
                                            {path.steps_count} steps
                                        </Badge>
                                        {path.target_career ? (
                                            <Badge variant="outline">
                                                → {path.target_career}
                                            </Badge>
                                        ) : null}
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                {paths.length === 0 ? (
                    <div className="mt-16 flex flex-col items-center gap-3 text-center text-muted-foreground">
                        <MapPinned className="size-10" />
                        <p>
                            No paths recorded yet. Verified paths are added as
                            data is collected.
                        </p>
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}
