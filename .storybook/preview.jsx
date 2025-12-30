import { useEffect } from "react";

export const decorators = [
    (Story) => {
        useEffect(() => {
            // Apply light mode by default in Storybook
            document.documentElement.classList.remove("dark");
            document.documentElement.classList.add("light");
        }, []);

        return (
            <div className="light min-h-screen bg-[var(--color-bg-primary)] text-[var(--color-text-primary)]">
                <Story />
            </div>
        );
    },
];

export const preview = {
    parameters: {
        layout: "centered",
        controls: {
            matchers: {
                color: /(background|color)$/i,
                date: /Date$/i,
            },
        },
    },
};
