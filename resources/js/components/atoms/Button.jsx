import clsx from "clsx";

const variantClasses = {
    primary:
        "bg-[var(--color-primary-600)] text-white hover:bg-[var(--color-primary-700)] active:bg-[var(--color-primary-800)] dark:bg-[var(--color-primary-500)] dark:hover:bg-[var(--color-primary-600)]",
    secondary:
        "bg-[var(--color-neutral-100)] text-[var(--color-text-primary)] hover:bg-[var(--color-neutral-200)] active:bg-[var(--color-neutral-300)] dark:bg-[var(--color-neutral-700)] dark:text-[var(--color-text-primary)] dark:hover:bg-[var(--color-neutral-600)]",
    ghost: "bg-transparent text-[var(--color-primary-600)] hover:bg-[var(--color-primary-50)] active:bg-[var(--color-primary-100)] dark:text-[var(--color-primary-400)] dark:hover:bg-[var(--color-neutral-800)]",
    danger: "bg-[var(--color-danger-600)] text-white hover:bg-[var(--color-danger-700)] active:bg-[var(--color-danger-800)] dark:bg-[var(--color-danger-500)] dark:hover:bg-[var(--color-danger-600)]",
    success:
        "bg-[var(--color-success-600)] text-white hover:bg-[var(--color-success-700)] active:bg-[var(--color-success-800)] dark:bg-[var(--color-success-500)] dark:hover:bg-[var(--color-success-600)]",
    warning:
        "bg-[var(--color-warning-600)] text-white hover:bg-[var(--color-warning-700)] active:bg-[var(--color-warning-800)] dark:bg-[var(--color-warning-500)] dark:hover:bg-[var(--color-warning-600)]",
};

const sizeClasses = {
    xs: "px-2 py-1 text-xs",
    sm: "px-3 py-1.5 text-sm",
    md: "px-4 py-2 text-sm",
    lg: "px-5 py-3 text-base",
    xl: "px-6 py-4 text-lg",
};

/**
 * Button Component
 *
 * Themeable button with multiple variants and sizes.
 *
 * @param {string} variant - Button style (primary, secondary, ghost, danger, success, warning)
 * @param {string} size - Button size (xs, sm, md, lg, xl)
 * @param {boolean} disabled - Disabled state
 * @param {string} className - Additional Tailwind classes
 * @param {React.ReactNode} children - Button content
 */
export const Button = ({
    variant = "primary",
    size = "md",
    disabled = false,
    className,
    children,
    ...props
}) => (
    <button
        disabled={disabled}
        className={clsx(
            "inline-flex items-center justify-center rounded-lg font-semibold transition-all focus-ring disabled:opacity-50 disabled:cursor-not-allowed",
            variantClasses[variant] ?? variantClasses.primary,
            sizeClasses[size] ?? sizeClasses.md,
            className
        )}
        {...props}
    >
        {children}
    </button>
);
