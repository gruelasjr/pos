import { Link, router, usePage } from "@inertiajs/react";
import { Card, CardBody } from "../atoms/Card";
import { Text } from "../atoms/Text";
import { Button } from "../atoms/Button";
import clsx from "clsx";
import useAuthStore from "../../store/authStore";
import { useMemo } from "react";

const navItems = [
    { label: "Dashboard", href: "/" },
    { label: "Productos", href: "/catalogo/productos" },
    { label: "Almacenes", href: "/catalogo/almacenes" },
    { label: "POS", href: "/pos" },
    { label: "Clientes", href: "/clientes" },
    { label: "Reportes", href: "/reportes" },
];

/**
 * AppSidebar Organism
 *
 * Main navigation sidebar with user profile section.
 */
export const AppSidebar = ({ user, token, onLogout }) => {
    const pathname =
        typeof window !== "undefined" ? window.location.pathname : "/";

    const activeItem = useMemo(
        () => navItems.find((item) => pathname.startsWith(item.href))?.href,
        [pathname]
    );

    return (
        <aside className="w-64 bg-[var(--color-bg-secondary)] border-r border-[var(--color-border-primary)] shadow-[var(--shadow-sm)] hidden md:flex flex-col">
            {/* Logo */}
            <div className="p-6 border-b border-[var(--color-border-primary)]">
                <Text as="h1" size="xl" weight="bold" tone="primary">
                    POS Faro
                </Text>
            </div>

            {/* Navigation */}
            <nav className="flex-1 px-4 py-6 space-y-2">
                {navItems.map((item) => (
                    <Link
                        key={item.href}
                        href={item.href}
                        className={clsx(
                            "block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors",
                            activeItem === item.href
                                ? "bg-[var(--color-primary-600)] text-white"
                                : "text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)]"
                        )}
                    >
                        {item.label}
                    </Link>
                ))}
            </nav>

            {/* User Profile */}
            {user && (
                <div className="p-4 border-t border-[var(--color-border-primary)]">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-[var(--color-primary-700)] text-sm font-semibold">
                            {user.name?.charAt(0)?.toUpperCase() ?? "?"}
                        </div>
                        <div className="min-w-0">
                            <Text
                                size="sm"
                                weight="semibold"
                                tone="primary"
                                className="truncate"
                            >
                                {user.name}
                            </Text>
                            <Text
                                size="xs"
                                tone="tertiary"
                                className="truncate"
                            >
                                {user.role}
                            </Text>
                        </div>
                    </div>
                    <Button
                        onClick={onLogout}
                        className="w-full"
                        size="sm"
                        variant="danger"
                    >
                        Salir
                    </Button>
                </div>
            )}
        </aside>
    );
};

/**
 * AppHeader Organism
 *
 * Top header bar with title and quick actions.
 */
export const AppHeader = ({ title, user }) => {
    return (
        <header className="bg-[var(--color-bg-primary)] border-b border-[var(--color-border-primary)] shadow-[var(--shadow-sm)] px-6 py-4">
            <div className="flex items-center justify-between">
                <div>
                    <Text
                        size="xs"
                        tone="tertiary"
                        weight="medium"
                        className="uppercase tracking-wider"
                    >
                        Panel
                    </Text>
                    <Text as="h1" size="2xl" weight="bold" tone="primary">
                        {title}
                    </Text>
                </div>
                <div className="flex items-center gap-4">
                    <Text
                        size="sm"
                        tone="secondary"
                        className="hidden sm:block"
                    >
                        {user?.email}
                    </Text>
                    <span
                        aria-hidden
                        className="hidden sm:block h-6 w-px bg-[var(--color-border-primary)]"
                    />
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => router.visit("/pos")}
                    >
                        Ir a cajas
                    </Button>
                </div>
            </div>
        </header>
    );
};
