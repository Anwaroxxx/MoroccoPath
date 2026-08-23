import { cn } from '@/lib/utils';

type Props = {
    title: string;
    subtitle?: string;
    actions?: React.ReactNode;
    className?: string;
};

/**
 * Consistent public-page heading block: eyebrow-free title, muted subtitle
 * and optional right-aligned actions.
 */
export default function PageHeader({
    title,
    subtitle,
    actions,
    className,
}: Props) {
    return (
        <div
            className={cn(
                'flex flex-wrap items-end justify-between gap-4',
                className,
            )}
        >
            <div>
                <h1 className="text-3xl font-bold tracking-tight text-foreground">
                    {title}
                </h1>
                {subtitle ? (
                    <p className="mt-2 max-w-2xl text-sm text-muted-foreground md:text-base">
                        {subtitle}
                    </p>
                ) : null}
            </div>
            {actions ? (
                <div className="flex flex-wrap items-center gap-2">
                    {actions}
                </div>
            ) : null}
        </div>
    );
}
