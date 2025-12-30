import { useTheme } from "../../context/ThemeContext";
import { Button } from "../atoms/Button";
import clsx from "clsx";

/**
 * ThemeToggle Molecule
 *
 * Button to switch between dark and light modes.
 */
export const ThemeToggle = ({ className }) => {
    const { isDark, toggleTheme } = useTheme();

    return (
        <Button
            onClick={toggleTheme}
            variant="ghost"
            size="sm"
            className={className}
            title={isDark ? "Switch to light mode" : "Switch to dark mode"}
        >
            {isDark ? (
                <span className="text-lg">☀️</span>
            ) : (
                <span className="text-lg">🌙</span>
            )}
        </Button>
    );
};
