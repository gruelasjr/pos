import { FormField } from "./FormField";
import { useState } from "react";

export default {
    title: "Molecules/FormField",
    component: FormField,
    parameters: {
        layout: "centered",
    },
    tags: ["autodocs"],
    argTypes: {
        label: {
            control: "text",
        },
        type: {
            control: "select",
            options: ["text", "email", "password", "number"],
        },
        error: {
            control: "text",
        },
        required: {
            control: "boolean",
        },
        disabled: {
            control: "boolean",
        },
    },
};

export const Default = {
    args: {
        label: "Email Address",
        type: "email",
        placeholder: "you@example.com",
    },
};

export const WithError = {
    args: {
        label: "Password",
        type: "password",
        error: "Password must be at least 8 characters",
    },
};

export const Required = {
    args: {
        label: "Full Name",
        type: "text",
        required: true,
        placeholder: "John Doe",
    },
};

export const Disabled = {
    args: {
        label: "Disabled Field",
        type: "text",
        disabled: true,
        value: "Cannot edit",
    },
};

export const FormExample = {
    render: () => {
        const [data, setData] = useState({
            name: "",
            email: "",
            password: "",
        });

        const [errors, setErrors] = useState({});

        const handleSubmit = (e) => {
            e.preventDefault();
            const newErrors = {};
            if (!data.name) newErrors.name = "Name is required";
            if (!data.email) newErrors.email = "Email is required";
            if (!data.password) newErrors.password = "Password is required";
            setErrors(newErrors);
        };

        return (
            <form onSubmit={handleSubmit} className="w-96 space-y-4">
                <FormField
                    label="Name"
                    type="text"
                    value={data.name}
                    onChange={(e) => setData({ ...data, name: e.target.value })}
                    error={errors.name}
                    required
                />
                <FormField
                    label="Email"
                    type="email"
                    value={data.email}
                    onChange={(e) =>
                        setData({ ...data, email: e.target.value })
                    }
                    error={errors.email}
                    required
                />
                <FormField
                    label="Password"
                    type="password"
                    value={data.password}
                    onChange={(e) =>
                        setData({ ...data, password: e.target.value })
                    }
                    error={errors.password}
                    required
                />
                <button
                    type="submit"
                    className="w-full bg-[var(--color-primary-600)] text-white py-2 rounded"
                >
                    Submit
                </button>
            </form>
        );
    },
};
