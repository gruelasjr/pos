import clsx from "clsx";

const sizes = {
    xs: "text-[var(--font-size-xs)]",
    sm: "text-[var(--font-size-sm)]",
    md: "text-[var(--font-size-md)]",
    lg: "text-[var(--font-size-lg)]",
    xl: "text-[var(--font-size-xl)]",
    "2xl": "text-[var(--font-size-2xl)]",
    "3xl": "text-[var(--font-size-3xl)]",
    "4xl": "text-[var(--font-size-4xl)]",
};

const weights = {
    normal: "font-[var(--font-weight-normal)]",
    medium: "font-[var(--font-weight-medium)]",
    semibold: "font-[var(--font-weight-semibold)]",
    bold: "font-[var(--font-weight-bold)]",
};

const tones = {
    primary: "text-[var(--color-text-primary)]",
    secondary: "text-[var(--color-text-secondary)]",
    tertiary: "text-[var(--color-text-tertiary)]",
    inverted: "text-[var(--color-text-inverted)]",
    success:
        "text-[var(--color-success-600)] dark:text-[var(--color-success-400)]",
    danger: "text-[var(--color-danger-600)] dark:text-[var(--color-danger-400)]",
    warning:
        "text-[var(--color-warning-600)] dark:text-[var(--color-warning-400)]",
    muted: "text-[var(--color-text-tertiary)]",
};

const lineHeights = {
    tight: "leading-[var(--line-height-tight)]",
    normal: "leading-[var(--line-height-normal)]",
    relaxed: "leading-[var(--line-height-relaxed)]",
};

/**
 * Text Component
 *
 * Flexible typography component for consistent text rendering.
 *
 * @param {string} as - HTML element tag (p, span, div, h1-h6, etc.)
 * @param {string} size - Font size
 * @param {string} weight - Font weight
 * @param {string} tone - Color tone
 * @param {string} lineHeight - Line height
 * @param {string} className - Additional classes
 * @param {React.ReactNode} children - Content
 */
export const Text = ({
    as: Component = "p",
    size = "md",
    weight = "normal",
    tone = "primary",
    lineHeight = "normal",
    className,
    children,
    ...props
}) => (
    <Component
        className={clsx(
            sizes[size] ?? sizes.md,
            weights[weight] ?? weights.normal,
            tones[tone] ?? tones.primary,
            lineHeights[lineHeight] ?? lineHeights.normal,
            className
        )}
        {...props}
    >
        {children}
    </Component>
);
