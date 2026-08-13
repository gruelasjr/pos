import clsx from "clsx";
import { useId } from "react";

export const Checkbox = ({ label, id, className, ...props }) => {
    const generatedId = useId();
    const fieldId = id ?? `checkbox-${generatedId}`;

    return (
        <label className={clsx("ui-checkbox", className)} htmlFor={fieldId}>
            <input id={fieldId} type="checkbox" {...props} />
            <span>{label}</span>
        </label>
    );
};
