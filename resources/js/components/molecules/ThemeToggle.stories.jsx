import { ThemeToggle } from "./ThemeToggle";

export default {
    title: "Molecules/ThemeToggle",
    component: ThemeToggle,
    parameters: {
        layout: "centered",
    },
    tags: ["autodocs"],
};

export const Default = {
    render: () => (
        <div className="flex items-center gap-4">
            <ThemeToggle />
            <span className="text-sm text-[var(--color-text-secondary)]">
                Click to toggle dark/light mode
            </span>
        </div>
    ),
};

export const InContext = {
    render: () => (
        <div className="light w-full min-h-screen bg-[var(--color-bg-primary)]">
            <div className="p-8">
                <h1 className="text-2xl font-bold text-[var(--color-text-primary)] mb-4">
                    Theme Toggle Example
                </h1>
                <ThemeToggle />
                <p className="text-[var(--color-text-secondary)] mt-4">
                    Click the toggle button above to switch between light and
                    dark modes.
                </p>
            </div>
        </div>
    ),
};
