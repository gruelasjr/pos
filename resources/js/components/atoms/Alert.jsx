import clsx from "clsx";

/**
 * Alert Component
 *
 * Message container with optional close action.
 */
export const Alert = ({
    variant = "info",
    onClose,
    className,
    children,
    ...props
}) => {
    const variants = {
        info: "bg-[var(--color-primary-50)] border border-[var(--color-primary-200)] text-[var(--color-primary-800)] dark:bg-[var(--color-primary-900)] dark:border-[var(--color-primary-700)] dark:text-[var(--color-primary-200)]",
        success:
            "bg-[var(--color-success-50)] border border-[var(--color-success-200)] text-[var(--color-success-800)] dark:bg-[var(--color-success-900)] dark:border-[var(--color-success-700)] dark:text-[var(--color-success-200)]",
        warning:
            "bg-[var(--color-warning-50)] border border-[var(--color-warning-200)] text-[var(--color-warning-800)] dark:bg-[var(--color-warning-900)] dark:border-[var(--color-warning-700)] dark:text-[var(--color-warning-200)]",
        danger: "bg-[var(--color-danger-50)] border border-[var(--color-danger-200)] text-[var(--color-danger-800)] dark:bg-[var(--color-danger-900)] dark:border-[var(--color-danger-700)] dark:text-[var(--color-danger-200)]",
    };

    return (
        <div
            className={clsx(
                "flex items-center justify-between rounded-lg px-4 py-3 text-sm",
                variants[variant] ?? variants.info,
                className
            )}
            role="alert"
            {...props}
        >
            <div>{children}</div>
            {onClose && (
                <button
                    type="button"
                    onClick={onClose}
                    className="ml-4 inline-flex text-current opacity-70 hover:opacity-100 transition-opacity"
                    aria-label="Close alert"
                >
                    ✕
                </button>
            )}
        </div>
    );
};
