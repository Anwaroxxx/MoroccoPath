import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, ArrowRight, CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { index as programsRoute } from '@/routes/programs';

type Profile = {
    education_level_id: number | null;
    qualification_id: number | null;
    bac_branch_codes: string[];
    interest_codes: string[];
    skill_codes: string[];
    career_goal_codes: string[];
    bac_grade: number | null;
    age: number | null;
    city: string | null;
    region: string | null;
    budget_max: number | null;
    willing_to_relocate: boolean;
};

const STEPS = ['Education', 'Interests', 'Skills & goals', 'Your situation'];

export default function Orientation({
    options,
    profile,
}: {
    options: {
        education_levels: Array<{ id: number; name: string }>;
        qualifications: Array<{ id: number; name: string }>;
        bac_branches: Array<{ code: string; name: string }>;
        interests: Array<{ code: string; name: string }>;
        skills: Array<{ code: string; name: string }>;
        careers: Array<{ code: string; name: string }>;
    };
    profile: Profile | null;
}) {
    const [step, setStep] = useState(0);
    const [form, setForm] = useState<Profile>(
        profile ?? {
            education_level_id: null,
            qualification_id: null,
            bac_branch_codes: [],
            interest_codes: [],
            skill_codes: [],
            career_goal_codes: [],
            bac_grade: null,
            age: null,
            city: null,
            region: null,
            budget_max: null,
            willing_to_relocate: false,
        },
    );
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const toggle = (
        field:
            | 'bac_branch_codes'
            | 'interest_codes'
            | 'skill_codes'
            | 'career_goal_codes',
        code: string,
    ) => {
        setForm((current) => ({
            ...current,
            [field]: current[field].includes(code)
                ? current[field].filter((value) => value !== code)
                : [...current[field], code],
        }));
    };

    const submit = () => {
        setProcessing(true);
        router.patch('/orientation', form, {
            onError: (bag) => {
                setErrors(bag);
                setProcessing(false);
            },
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Home', href: '/' },
                { title: 'Find your path', href: '/orientation' },
            ]}
        >
            <Head title="Orientation" />
            <div className="mx-auto min-h-screen max-w-3xl p-4 md:p-6">
                {/* Progress */}
                <ol className="mb-6 flex flex-wrap gap-2 text-xs">
                    {STEPS.map((label, i) => (
                        <li
                            key={label}
                            className={`flex items-center gap-1 rounded-full border px-3 py-1 ${
                                i === step
                                    ? 'border-primary font-medium text-primary'
                                    : i < step
                                      ? 'text-emerald-600'
                                      : 'text-muted-foreground'
                            }`}
                        >
                            {i < step ? (
                                <CheckCircle2 className="size-3" />
                            ) : null}
                            {i + 1}. {label}
                        </li>
                    ))}
                </ol>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {step === 0
                                ? 'Where are you right now?'
                                : STEPS[step]}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {step === 0 ? (
                            <>
                                <Field label="Current education level">
                                    <select
                                        className="w-full rounded-md border bg-background px-3 py-2"
                                        value={form.education_level_id ?? ''}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                education_level_id: e.target
                                                    .value
                                                    ? Number(e.target.value)
                                                    : null,
                                            })
                                        }
                                    >
                                        <option value="">Select…</option>
                                        {options.education_levels.map(
                                            (level) => (
                                                <option
                                                    key={level.id}
                                                    value={level.id}
                                                >
                                                    {level.name}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </Field>
                                <Field label="Diploma already earned (optional)">
                                    <select
                                        className="w-full rounded-md border bg-background px-3 py-2"
                                        value={form.qualification_id ?? ''}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                qualification_id: e.target.value
                                                    ? Number(e.target.value)
                                                    : null,
                                            })
                                        }
                                    >
                                        <option value="">None</option>
                                        {options.qualifications.map(
                                            (qualification) => (
                                                <option
                                                    key={qualification.id}
                                                    value={qualification.id}
                                                >
                                                    {qualification.name}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </Field>
                                <Field label="Bac branch (if you have or are doing the Bac)">
                                    <Chips
                                        options={options.bac_branches}
                                        selected={form.bac_branch_codes}
                                        onToggle={(code) =>
                                            toggle('bac_branch_codes', code)
                                        }
                                    />
                                </Field>
                                <Field label="Bac grade /20 (optional)">
                                    <Input
                                        type="number"
                                        min="0"
                                        max="20"
                                        step="0.25"
                                        value={form.bac_grade ?? ''}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                bac_grade:
                                                    e.target.value === ''
                                                        ? null
                                                        : Number(
                                                              e.target.value,
                                                          ),
                                            })
                                        }
                                        className="max-w-32"
                                    />
                                </Field>
                            </>
                        ) : null}

                        {step === 1 ? (
                            <Field label="What fields interest you? Pick any.">
                                <Chips
                                    options={options.interests}
                                    selected={form.interest_codes}
                                    onToggle={(code) =>
                                        toggle('interest_codes', code)
                                    }
                                />
                            </Field>
                        ) : null}

                        {step === 2 ? (
                            <>
                                <Field label="What are you good at? (optional)">
                                    <Chips
                                        options={options.skills}
                                        selected={form.skill_codes}
                                        onToggle={(code) =>
                                            toggle('skill_codes', code)
                                        }
                                    />
                                </Field>
                                <Field label="What do you want to become? (skip if unsure — that's fine)">
                                    <Chips
                                        options={options.careers}
                                        selected={form.career_goal_codes}
                                        onToggle={(code) =>
                                            toggle('career_goal_codes', code)
                                        }
                                    />
                                </Field>
                            </>
                        ) : null}

                        {step === 3 ? (
                            <>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field label="City">
                                        <Input
                                            value={form.city ?? ''}
                                            onChange={(e) =>
                                                setForm({
                                                    ...form,
                                                    city: e.target.value,
                                                })
                                            }
                                            placeholder="Casablanca"
                                        />
                                    </Field>
                                    <Field label="Region">
                                        <Input
                                            value={form.region ?? ''}
                                            onChange={(e) =>
                                                setForm({
                                                    ...form,
                                                    region: e.target.value,
                                                })
                                            }
                                            placeholder="Casablanca-Settat"
                                        />
                                    </Field>
                                    <Field label="Age">
                                        <Input
                                            type="number"
                                            min="10"
                                            max="80"
                                            value={form.age ?? ''}
                                            onChange={(e) =>
                                                setForm({
                                                    ...form,
                                                    age:
                                                        e.target.value === ''
                                                            ? null
                                                            : Number(
                                                                  e.target
                                                                      .value,
                                                              ),
                                                })
                                            }
                                        />
                                    </Field>
                                    <Field label="Max monthly/annual budget you can spend (MAD)">
                                        <Input
                                            type="number"
                                            min="0"
                                            value={form.budget_max ?? ''}
                                            onChange={(e) =>
                                                setForm({
                                                    ...form,
                                                    budget_max:
                                                        e.target.value === ''
                                                            ? null
                                                            : Number(
                                                                  e.target
                                                                      .value,
                                                              ),
                                                })
                                            }
                                        />
                                    </Field>
                                </div>
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={form.willing_to_relocate}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                willing_to_relocate:
                                                    e.target.checked,
                                            })
                                        }
                                    />
                                    I am willing to relocate for the right
                                    program
                                </label>
                                {Object.entries(errors).map(
                                    ([key, message]) => (
                                        <p
                                            key={key}
                                            className="text-sm text-red-600"
                                        >
                                            {Array.isArray(message)
                                                ? message[0]
                                                : message}
                                        </p>
                                    ),
                                )}
                            </>
                        ) : null}

                        <div className="flex items-center justify-between pt-2">
                            <Button
                                variant="ghost"
                                disabled={step === 0}
                                onClick={() =>
                                    setStep((current) => current - 1)
                                }
                            >
                                <ArrowLeft className="size-4" /> Back
                            </Button>
                            {step < STEPS.length - 1 ? (
                                <Button
                                    onClick={() =>
                                        setStep((current) => current + 1)
                                    }
                                >
                                    Continue <ArrowRight className="size-4" />
                                </Button>
                            ) : (
                                <Button onClick={submit} disabled={processing}>
                                    {processing ? 'Saving…' : 'See my paths'}
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <p className="mt-4 text-center text-sm text-muted-foreground">
                    Not sure about something? Skip it — every question is
                    optional.
                </p>
                <p className="mt-2 text-center text-sm">
                    <Link
                        href={programsRoute().url}
                        className="underline underline-offset-4"
                    >
                        Or just browse all programs
                    </Link>
                </p>
            </div>
        </AppLayout>
    );
}

function Field({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <label className="block space-y-2">
            <span className="text-sm font-medium">{label}</span>
            {children}
        </label>
    );
}

function Chips({
    options,
    selected,
    onToggle,
}: {
    options: Array<{ id?: number; code?: string; name: string }>;
    selected: string[];
    onToggle: (code: string) => void;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            {options.map((option) => {
                const code = option.code ?? String(option.id ?? '');
                const active = selected.includes(code);

                return (
                    <button
                        type="button"
                        key={code || option.name}
                        onClick={() => onToggle(code)}
                        className={`rounded-full border px-3 py-1 text-sm transition-colors ${
                            active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-muted'
                        }`}
                    >
                        {option.name}
                    </button>
                );
            })}
        </div>
    );
}
