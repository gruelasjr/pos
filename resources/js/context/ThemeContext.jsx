/**
 * Theme Context Provider
 * Manages dark/light mode toggle and persistence
 */
import { createContext, useContext, useEffect, useState } from "react";

const ThemeContext = createContext();

export const ThemeProvider = ({ children }) => {
    const [isDark, setIsDark] = useState(false);

    useEffect(() => {
        // Check localStorage or system preference
        const stored = localStorage.getItem("theme");
        if (stored) {
            setIsDark(stored === "dark");
        } else if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
            setIsDark(true);
        }
    }, []);

    useEffect(() => {
        const root = document.documentElement;
        root.className = isDark ? "dark" : "light";
        localStorage.setItem("theme", isDark ? "dark" : "light");
    }, [isDark]);

    const toggleTheme = () => setIsDark((prev) => !prev);

    return (
        <ThemeContext.Provider value={{ isDark, toggleTheme }}>
            {children}
        </ThemeContext.Provider>
    );
};

export const useTheme = () => {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error("useTheme must be used within ThemeProvider");
    }
    return context;
};
