import clsx from "clsx";

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
            "ui-button",
            `ui-button--${variant}`,
            `ui-button--${size}`,
            className
        )}
        {...props}
    >
        {children}
    </button>
);
