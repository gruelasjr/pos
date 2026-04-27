import { useTheme } from "../../context/ThemeContext";
import { Button } from "../atoms/Button";

/**
 * ThemeToggle Molecule
 *
 * Button to switch between dark and light modes.
 */
export const ThemeToggle = ({ className }) => {
    const { isDark, toggleTheme } = useTheme();

    return (
        <Button
            aria-label={isDark ? "Switch to light theme" : "Switch to dark theme"}
            onClick={toggleTheme}
            variant="ghost"
            size="sm"
            className={className}
            title={isDark ? "Switch to light mode" : "Switch to dark mode"}
        >
            {isDark ? "Light" : "Dark"}
        </Button>
    );
};
