import clsx from "clsx";
import { useId } from "react";
import { Text } from "../atoms/Text";
import { Input, Select, Textarea } from "../atoms/FormInputs";

/**
 * FormField Molecule
 *
 * Complete form field with label, input, and error message.
 */
export const FormField = ({
    label,
    error,
    required,
    type = "text",
    as = "input",
    className,
    ...props
}) => {
    const generatedId = useId();
    const fieldId = props.id ?? `field-${generatedId}`;
    const Component =
        as === "textarea" ? Textarea : as === "select" ? Select : Input;

    return (
        <div className={clsx("space-y-2", className)}>
            {label && (
                <label htmlFor={fieldId} className="block">
                    <Text size="sm" weight="medium" tone="primary">
                        {label}
                        {required && (
                            <span className="text-[var(--color-danger-600)]">
                                {" "}
                                *
                            </span>
                        )}
                    </Text>
                </label>
            )}
            <Component
                id={fieldId}
                type={as === "input" ? type : undefined}
                {...props}
            />
            {error && (
                <Text size="xs" tone="danger">
                    {error}
                </Text>
            )}
        </div>
    );
};
