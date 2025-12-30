import clsx from "clsx";

/**
 * Badge Component
 *
 * Small label element for categorization.
 */
export const Badge = ({
    variant = "primary",
    size = "md",
    className,
    children,
    ...props
}) => {
    const variants = {
        primary:
            "bg-[var(--color-primary-100)] text-[var(--color-primary-800)] dark:bg-[var(--color-primary-900)] dark:text-[var(--color-primary-300)]",
        success:
            "bg-[var(--color-success-100)] text-[var(--color-success-800)] dark:bg-[var(--color-success-900)] dark:text-[var(--color-success-300)]",
        danger: "bg-[var(--color-danger-100)] text-[var(--color-danger-800)] dark:bg-[var(--color-danger-900)] dark:text-[var(--color-danger-300)]",
        warning:
            "bg-[var(--color-warning-100)] text-[var(--color-warning-800)] dark:bg-[var(--color-warning-900)] dark:text-[var(--color-warning-300)]",
        secondary:
            "bg-[var(--color-neutral-100)] text-[var(--color-neutral-800)] dark:bg-[var(--color-neutral-800)] dark:text-[var(--color-neutral-200)]",
    };

    const sizes = {
        sm: "px-2 py-0.5 text-xs",
        md: "px-3 py-1 text-sm",
        lg: "px-4 py-1.5 text-base",
    };

    return (
        <span
            className={clsx(
                "inline-flex items-center rounded-full font-semibold transition-colors",
                variants[variant] ?? variants.primary,
                sizes[size] ?? sizes.md,
                className
            )}
            {...props}
        >
            {children}
        </span>
    );
};

/**
 * Divider Component
 *
 * Visual separator line.
 */
export const Divider = ({
    className,
    orientation = "horizontal",
    ...props
}) => (
    <div
        className={clsx(
            orientation === "horizontal"
                ? "border-b border-[var(--color-border-primary)] my-4"
                : "border-r border-[var(--color-border-primary)] mx-4 h-6",
            className
        )}
        {...props}
    />
);

/**
 * Spinner Component
 *
 * Loading indicator.
 */
export const Spinner = ({ size = "md", className, ...props }) => {
    const sizes = {
        sm: "w-4 h-4",
        md: "w-8 h-8",
        lg: "w-12 h-12",
    };

    return (
        <div
            className={clsx(
                "inline-block rounded-full border-4 border-[var(--color-border-primary)] border-t-[var(--color-primary-600)] animate-spin",
                sizes[size] ?? sizes.md,
                className
            )}
            {...props}
        />
    );
};
