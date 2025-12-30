import { Text } from "./Text";

export default {
    title: "Atoms/Text",
    component: Text,
    parameters: {
        layout: "centered",
    },
    tags: ["autodocs"],
    argTypes: {
        size: {
            control: "select",
            options: ["xs", "sm", "md", "lg", "xl", "2xl", "3xl", "4xl"],
        },
        weight: {
            control: "select",
            options: ["normal", "medium", "semibold", "bold"],
        },
        tone: {
            control: "select",
            options: [
                "primary",
                "secondary",
                "tertiary",
                "inverted",
                "success",
                "danger",
                "warning",
                "muted",
            ],
        },
        as: {
            control: "select",
            options: ["p", "span", "div", "h1", "h2", "h3"],
        },
    },
};

export const Default = {
    args: {
        children: "Default Text",
    },
};

export const Sizes = {
    render: () => (
        <div className="flex flex-col gap-4">
            <Text size="xs">Extra Small Text</Text>
            <Text size="sm">Small Text</Text>
            <Text size="md">Medium Text</Text>
            <Text size="lg">Large Text</Text>
            <Text size="xl">Extra Large Text</Text>
            <Text size="2xl">2XL Text</Text>
            <Text size="3xl">3XL Text</Text>
            <Text size="4xl">4XL Text</Text>
        </div>
    ),
};

export const Weights = {
    render: () => (
        <div className="flex flex-col gap-4">
            <Text weight="normal">Normal Weight</Text>
            <Text weight="medium">Medium Weight</Text>
            <Text weight="semibold">Semibold Weight</Text>
            <Text weight="bold">Bold Weight</Text>
        </div>
    ),
};

export const Tones = {
    render: () => (
        <div className="flex flex-col gap-4">
            <Text tone="primary">Primary Tone</Text>
            <Text tone="secondary">Secondary Tone</Text>
            <Text tone="tertiary">Tertiary Tone</Text>
            <Text tone="success">Success Tone</Text>
            <Text tone="danger">Danger Tone</Text>
            <Text tone="warning">Warning Tone</Text>
            <Text tone="muted">Muted Tone</Text>
        </div>
    ),
};

export const AsHeading = {
    args: {
        as: "h2",
        size: "2xl",
        weight: "bold",
        children: "This is a Heading",
    },
};
