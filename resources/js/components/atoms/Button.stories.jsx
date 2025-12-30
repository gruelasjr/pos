import { Button } from "./Button";

export default {
    title: "Atoms/Button",
    component: Button,
    parameters: {
        layout: "centered",
    },
    tags: ["autodocs"],
    argTypes: {
        variant: {
            control: "select",
            options: [
                "primary",
                "secondary",
                "ghost",
                "danger",
                "success",
                "warning",
            ],
        },
        size: {
            control: "select",
            options: ["xs", "sm", "md", "lg", "xl"],
        },
        disabled: {
            control: "boolean",
        },
        children: {
            control: "text",
        },
    },
};

export const Primary = {
    args: {
        variant: "primary",
        children: "Primary Button",
    },
};

export const Secondary = {
    args: {
        variant: "secondary",
        children: "Secondary Button",
    },
};

export const Ghost = {
    args: {
        variant: "ghost",
        children: "Ghost Button",
    },
};

export const Danger = {
    args: {
        variant: "danger",
        children: "Delete",
    },
};

export const Success = {
    args: {
        variant: "success",
        children: "Save",
    },
};

export const Warning = {
    args: {
        variant: "warning",
        children: "Warning",
    },
};

export const Sizes = {
    render: () => (
        <div className="flex gap-2">
            <Button size="xs">Extra Small</Button>
            <Button size="sm">Small</Button>
            <Button size="md">Medium</Button>
            <Button size="lg">Large</Button>
            <Button size="xl">Extra Large</Button>
        </div>
    ),
};

export const Disabled = {
    args: {
        variant: "primary",
        children: "Disabled Button",
        disabled: true,
    },
};

export const AllVariants = {
    render: () => (
        <div className="flex flex-col gap-4">
            <Button variant="primary">Primary</Button>
            <Button variant="secondary">Secondary</Button>
            <Button variant="ghost">Ghost</Button>
            <Button variant="danger">Danger</Button>
            <Button variant="success">Success</Button>
            <Button variant="warning">Warning</Button>
        </div>
    ),
};
