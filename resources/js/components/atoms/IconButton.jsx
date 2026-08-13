import clsx from "clsx";

export const IconButton = ({ label, variant = "secondary", size = "md", className, children, ...props }) => (
    <button
        type="button"
        aria-label={label}
        className={clsx("ui-icon-button", `ui-icon-button--${variant}`, `ui-icon-button--${size}`, className)}
        {...props}
    >
        {children}
    </button>
);
