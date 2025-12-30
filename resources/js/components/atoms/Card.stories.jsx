import { Card, CardBody } from "./Card";
import { Text } from "./Text";

export default {
    title: "Atoms/Card",
    component: Card,
    parameters: {
        layout: "centered",
    },
    tags: ["autodocs"],
};

export const Default = {
    render: () => (
        <Card className="w-80">
            <CardBody>
                <Text weight="semibold" size="lg">
                    Card Title
                </Text>
                <Text tone="secondary">
                    This is card content with a description.
                </Text>
            </CardBody>
        </Card>
    ),
};

export const WithMultipleElements = {
    render: () => (
        <Card className="w-96">
            <CardBody>
                <Text as="h3" weight="bold" size="lg">
                    Card Heading
                </Text>
                <Text tone="secondary" className="mb-4">
                    Cards are flexible containers with light styling.
                </Text>
                <div className="text-sm text-[var(--color-text-tertiary)]">
                    Perfect for organizing content.
                </div>
            </CardBody>
        </Card>
    ),
};

export const MultipleCards = {
    render: () => (
        <div className="grid grid-cols-3 gap-4">
            {["Card 1", "Card 2", "Card 3"].map((title) => (
                <Card key={title}>
                    <CardBody>
                        <Text weight="semibold">{title}</Text>
                        <Text tone="secondary" size="sm">
                            Content
                        </Text>
                    </CardBody>
                </Card>
            ))}
        </div>
    ),
};
