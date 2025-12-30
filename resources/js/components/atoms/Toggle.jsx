import clsx from "clsx";
import { useId } from "react";

/**
 * Toggle Component
 *
 * Accessible switch built with native checkbox semantics and theme tokens.
 *
 * @param {boolean} checked  Current toggle state.
 * @param {(next: boolean) => void} onChange  Change handler.
 * @param {boolean} disabled  Disabled state.
 * @param {string} label  Optional label rendered to the right of the control.
 * @param {string} helper  Optional helper text below the label.
 * @param {string} className  Additional class names.
 */
export const Toggle = ({
    checked = false,
    onChange,
    disabled = false,
    label,
    helper,
    className,
    ...props
}) => {
    const id = useId();

    return (
        <label
            htmlFor={id}
            className={clsx(
                "inline-flex items-center gap-3 select-none",
                disabled ? "opacity-60 cursor-not-allowed" : "cursor-pointer",
                className
            )}
        >
            <span className="relative inline-flex items-center">
                <input
                    id={id}
                    type="checkbox"
                    className="sr-only"
                    checked={checked}
                    onChange={(event) => onChange?.(event.target.checked)}
                    disabled={disabled}
                    {...props}
                />
                <span
                    aria-hidden
                    className={clsx(
                        "w-11 h-6 rounded-full transition-colors duration-200 flex items-center px-1",
                        checked
                            ? "bg-[var(--color-primary-600)] dark:bg-[var(--color-primary-500)]"
                            : "bg-[var(--color-neutral-300)] dark:bg-[var(--color-neutral-700)]"
                    )}
                >
                    <span
                        className={clsx(
                            "h-4 w-4 rounded-full bg-white shadow-[var(--shadow-sm)] transform transition-transform duration-200",
                            checked ? "translate-x-5" : "translate-x-0"
                        )}
                    />
                </span>
            </span>
            <span className="flex flex-col">
                {label && (
                    <span className="text-sm text-[var(--color-text-primary)]">
                        {label}
                    </span>
                )}
                {helper && (
                    <span className="text-xs text-[var(--color-text-secondary)]">
                        {helper}
                    </span>
                )}
            </span>
        </label>
    );
};
