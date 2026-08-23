import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    BadgeCheck,
    CalendarDays,
    CircleDollarSign,
    GraduationCap,
    MapPin,
    Route,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index as programsRoute } from '@/routes/programs';

type Cost = {
    type: string;
    label: string;
    amount_min: number | null;
    amount_max: number | null;
    currency: string;
    is_free: boolean;
    academic_year: string | null;
};

type Program = {
    name: string;
    slug: string;
    description: string | null;
    study_mode: string;
    duration_label: string | null;
    language: string | null;
    institution_name: string;
    institution_slug: string;
    city: string | null;
    is_free: boolean;
    costs: Cost[];
    version: {
        academic_year: string;
        status: string;
        admission_information: string | null;
        application_start: string | null;
        application_end: string | null;
    } | null;
    requirements: string[];
    process_steps: string[];
    careers: string[];
    alternatives: Array<{ slug: string; name: string }>;
};

export default function ProgramShow({ program }: { program: Program }) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Home', href: '/' },
                { title: 'Programs', href: programsRoute().url },
                { title: program.name, href: `/programs/${program.slug}` },
            ]}
        >
            <Head title={program.name} />
            <div className="min-h-screen p-4 md:p-6">
                <Link
                    href={programsRoute().url}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All programs
                </Link>

                <header className="mt-4">
                    <h1 className="text-3xl font-bold tracking-tight">
                        {program.name}
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        {program.institution_name}
                        {program.city ? ` · ${program.city}` : ''}
                    </p>
                    <div className="mt-3 flex flex-wrap gap-2 text-xs">
                        <Badge variant="outline">
                            {program.study_mode.replace('_', ' ')}
                        </Badge>
                        {program.duration_label ? (
                            <Badge variant="outline">
                                {program.duration_label}
                            </Badge>
                        ) : null}
                        {program.language ? (
                            <Badge variant="outline">{program.language}</Badge>
                        ) : null}
                        {program.is_free ? (
                            <Badge className="bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300">
                                Free
                            </Badge>
                        ) : null}
                    </div>
                </header>

                {program.description ? (
                    <p className="mt-6 max-w-3xl leading-relaxed">
                        {program.description}
                    </p>
                ) : null}

                <div className="mt-8 grid gap-6 lg:grid-cols-3">
                    {/* Requirements */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <GraduationCap className="size-5 text-primary" />{' '}
                                Who can apply
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {program.requirements.length > 0 ? (
                                program.requirements.map((requirement) => (
                                    <p
                                        key={requirement}
                                        className="flex items-start gap-2 text-sm"
                                    >
                                        <BadgeCheck className="mt-0.5 size-4 shrink-0 text-primary" />
                                        {requirement}
                                    </p>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Requirements for this program have not been
                                    verified yet.
                                </p>
                            )}
                            {program.process_steps.length > 0 ? (
                                <div className="mt-4 rounded-lg border bg-muted/40 p-3">
                                    <p className="mb-1 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        Admission process
                                    </p>
                                    {program.process_steps.map((step) => (
                                        <p key={step} className="text-sm">
                                            • {step}
                                        </p>
                                    ))}
                                </div>
                            ) : null}
                        </CardContent>
                    </Card>

                    {/* Practical facts */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <CircleDollarSign className="size-5 text-primary" />{' '}
                                    Cost
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                {program.costs.length === 0 ? (
                                    <p className="text-muted-foreground">
                                        Data not yet verified.
                                    </p>
                                ) : (
                                    program.costs.map((cost) => (
                                        <div
                                            key={`${cost.type}-${cost.academic_year ?? ''}`}
                                        >
                                            {cost.is_free ? (
                                                <span className="font-medium text-emerald-600 dark:text-emerald-400">
                                                    Free
                                                </span>
                                            ) : (
                                                <span>
                                                    {cost.label}:{' '}
                                                    {cost.amount_min ?? '?'}–
                                                    {cost.amount_max ?? '?'}{' '}
                                                    {cost.currency}
                                                </span>
                                            )}
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <MapPin className="size-5 text-primary" />{' '}
                                    Where
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm text-muted-foreground">
                                {program.city ?? 'Multiple locations'}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <CalendarDays className="size-5 text-primary" />{' '}
                                    When to apply
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-1 text-sm">
                                {!program.version ? (
                                    <p className="text-muted-foreground">
                                        Data not yet verified.
                                    </p>
                                ) : (
                                    <>
                                        {program.version.application_start ? (
                                            <p>
                                                Opens{' '}
                                                {
                                                    program.version
                                                        .application_start
                                                }
                                            </p>
                                        ) : null}
                                        {program.version.application_end ? (
                                            <p>
                                                Closes{' '}
                                                {
                                                    program.version
                                                        .application_end
                                                }
                                            </p>
                                        ) : null}
                                        {!program.version.application_start &&
                                        !program.version.application_end ? (
                                            <p className="text-muted-foreground">
                                                Dates not published.
                                            </p>
                                        ) : null}
                                        <p className="text-xs text-muted-foreground">
                                            Academic year{' '}
                                            {program.version.academic_year} (
                                            {program.version.status})
                                        </p>
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Careers + alternatives */}
                <div className="mt-8 grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Route className="size-5 text-primary" /> What
                                you can become
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-wrap gap-2">
                            {program.careers.length > 0 ? (
                                program.careers.map((career) => (
                                    <Badge key={career}>{career}</Badge>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Career outcomes not recorded yet.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Not eligible? Alternatives
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {program.alternatives.length > 0 ? (
                                program.alternatives.map((alternative) => (
                                    <Link
                                        key={alternative.slug}
                                        href={`/programs/${alternative.slug}`}
                                        className="block underline underline-offset-2"
                                    >
                                        → {alternative.name}
                                    </Link>
                                ))
                            ) : (
                                <p className="text-muted-foreground">
                                    No alternatives recorded for this program
                                    yet.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
