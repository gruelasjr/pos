import clsx from "clsx";

/**
 * Input Component
 *
 * Themed text input with consistent styling.
 */
export const Input = ({ className, disabled, ...props }) => (
    <input
        disabled={disabled}
        className={clsx(
            "ui-control",
            className
        )}
        {...props}
    />
);

/**
 * Select Component
 *
 * Themed select dropdown.
 */
export const Select = ({ className, disabled, children, ...props }) => (
    <select
        disabled={disabled}
        className={clsx(
            "ui-control",
            className
        )}
        {...props}
    >
        {children}
    </select>
);

/**
 * Textarea Component
 *
 * Multi-line text input.
 */
export const Textarea = ({ className, disabled, ...props }) => (
    <textarea
        disabled={disabled}
        className={clsx(
            "ui-control ui-control--textarea",
            className
        )}
        {...props}
    />
);
