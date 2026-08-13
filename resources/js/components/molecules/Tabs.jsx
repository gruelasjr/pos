import clsx from "clsx";

export const Tabs = ({ items, value, onChange, label, className }) => (
    <div className={clsx("ui-tabs", className)} role="tablist" aria-label={label}>
        {items.map(item => (
            <button
                type="button"
                role="tab"
                aria-selected={value === item.value}
                className={value === item.value ? "is-active" : undefined}
                key={item.value}
                onClick={() => onChange(item.value)}
            >
                {item.label}
            </button>
        ))}
    </div>
);
