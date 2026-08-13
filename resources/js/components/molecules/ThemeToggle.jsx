import { useTheme } from "../../context/ThemeContext";
import { Moon, Sun } from "lucide-react";

/**
 * ThemeToggle Molecule
 *
 * Button to switch between dark and light modes.
 */
export const ThemeToggle = ({ className }) => {
    const { isDark, toggleTheme } = useTheme();

    return (
        <button
            aria-label={isDark ? "Cambiar a tema claro" : "Cambiar a tema oscuro"}
            onClick={toggleTheme}
            className={`icon-only ${className || ""}`}
            title={isDark ? "Tema claro" : "Tema oscuro"}
        >
            {isDark ? <Sun size={19}/> : <Moon size={19}/>}
        </button>
    );
};
