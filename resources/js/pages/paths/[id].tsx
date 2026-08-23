import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CircleCheck, GitBranch } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

type Step = {
    id: number;
    title: string;
    description: string | null;
    education_level: string | null;
    program: { slug: string; name: string; published: boolean } | null;
    children: Step[];
};

type PathDetail = {
    name: string;
    slug: string;
    description: string | null;
    field: string | null;
    target_career: string | null;
    steps: Step[];
};

export default function PathShow({ path }: { path: PathDetail }) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Home', href: '/' },
                { title: 'Paths', href: '/paths' },
                { title: path.name, href: `/paths/${path.slug}` },
            ]}
        >
            <Head title={path.name} />
            <div className="min-h-screen p-4 md:p-6">
                <Link
                    href="/paths"
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All paths
                </Link>

                <header className="mt-4 flex flex-wrap items-center gap-2">
                    <h1 className="text-3xl font-bold tracking-tight">
                        {path.name}
                    </h1>
                    {path.target_career ? (
                        <Badge>→ {path.target_career}</Badge>
                    ) : null}
                    {path.slug.startsWith('demo') ? (
                        <Badge
                            variant="outline"
                            className="text-muted-foreground"
                        >
                            demonstration data
                        </Badge>
                    ) : null}
                </header>
                {path.description ? (
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        {path.description}
                    </p>
                ) : null}

                <div className="mt-8 grid gap-6 lg:grid-cols-2">
                    {path.steps.map((root) => (
                        <Card key={root.id}>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <GitBranch className="size-5 text-primary" />{' '}
                                    Entry point
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <StepNode node={root} />
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {path.steps.length === 0 ? (
                    <p className="mt-10 text-center text-muted-foreground">
                        No steps recorded for this path yet.
                    </p>
                ) : null}
            </div>
        </AppLayout>
    );
}

function StepNode({ node, last = false }: { node: Step; last?: boolean }) {
    return (
        <div className={`space-y-4 ${last ? '' : 'mb-4'}`}>
            <StepCard node={node} />
            {node.children.length > 0 ? (
                <div className="ml-4 space-y-4 border-l-2 border-primary/30 pl-4">
                    {node.children.map((child) => (
                        <StepNode key={child.id} node={child} />
                    ))}
                </div>
            ) : null}
        </div>
    );
}

function StepCard({ node }: { node: Step }) {
    const isLeaf = node.children.length === 0;

    return (
        <div className="rounded-xl border p-4">
            <p className="flex items-center gap-2 font-medium">
                {isLeaf ? (
                    <CircleCheck className="size-4 text-emerald-500" />
                ) : null}
                {node.title}
            </p>
            {node.education_level ? (
                <p className="mt-1 text-xs text-muted-foreground">
                    Level: {node.education_level}
                </p>
            ) : null}
            {node.description ? (
                <p className="mt-1 text-sm text-muted-foreground">
                    {node.description}
                </p>
            ) : null}
            {node.program && node.program.published ? (
                <Link
                    href={`/programs/${node.program.slug}`}
                    className="mt-2 inline-flex items-center gap-1 text-sm underline underline-offset-2"
                >
                    See the program <ArrowRight className="size-3" />
                </Link>
            ) : null}
        </div>
    );
}
