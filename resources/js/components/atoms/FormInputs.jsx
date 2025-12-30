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
            "w-full px-4 py-2 text-sm bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-lg text-[var(--color-text-primary)] placeholder-[var(--color-text-tertiary)] transition-colors focus-ring disabled:opacity-50 disabled:cursor-not-allowed",
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
            "w-full px-4 py-2 text-sm bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-lg text-[var(--color-text-primary)] transition-colors focus-ring disabled:opacity-50 disabled:cursor-not-allowed",
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
            "w-full px-4 py-2 text-sm bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-lg text-[var(--color-text-primary)] placeholder-[var(--color-text-tertiary)] transition-colors focus-ring disabled:opacity-50 disabled:cursor-not-allowed resize-vertical",
            className
        )}
        {...props}
    />
);
