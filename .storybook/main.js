module.exports = {
    stories: ["../resources/js/components/**/*.stories.{js,jsx,ts,tsx}"],
    addons: ["@storybook/addon-links", "@storybook/addon-essentials"],
    framework: {
        name: "@storybook/react-vite",
        options: {},
    },
    docs: {
        autodocs: true,
    },
};
