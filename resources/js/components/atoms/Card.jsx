import clsx from "clsx";

/**
 * Card Component
 *
 * Container with themed background and border.
 * Works with CardBody for consistent padding.
 */
export const Card = ({ className, children, ...props }) => (
    <div
        className={clsx(
            "bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow-[var(--shadow-sm)] transition-shadow hover:shadow-[var(--shadow-md)]",
            className
        )}
        {...props}
    >
        {children}
    </div>
);

/**
 * CardBody Component
 *
 * Padding wrapper for card content.
 */
export const CardBody = ({ className, children, ...props }) => (
    <div className={clsx("p-6", className)} {...props}>
        {children}
    </div>
);
