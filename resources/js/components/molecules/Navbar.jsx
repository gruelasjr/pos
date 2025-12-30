import { Link } from "@inertiajs/react";
import { useState } from "react";
import { Button } from "../atoms/Button";
import { Text } from "../atoms/Text";
import { ThemeToggle } from "./ThemeToggle";
import clsx from "clsx";

/**
 * Navbar Molecule
 *
 * Main navigation bar with theme toggle.
 */
export const Navbar = ({ user }) => {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <nav className="bg-[var(--color-bg-secondary)] border-b border-[var(--color-border-primary)] shadow-[var(--shadow-sm)]">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center py-4">
                    <div className="flex items-center space-x-6">
                        <Link
                            href="/"
                            className="text-[var(--color-text-primary)] hover:text-[var(--color-primary-600)] transition-colors"
                        >
                            <Text weight="semibold">Inicio</Text>
                        </Link>
                        <Link
                            href={route("swift-auth.users.index")}
                            className="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors"
                        >
                            Usuarios
                        </Link>
                        <Link
                            href={route("swift-auth.roles.index")}
                            className="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors"
                        >
                            Roles
                        </Link>
                    </div>

                    <div className="hidden sm:flex items-center space-x-4">
                        <ThemeToggle />
                        {user ? (
                            <>
                                <Text size="sm" tone="secondary">
                                    {user.name}
                                </Text>
                                <form
                                    method="GET"
                                    action={route("swift-auth.logout")}
                                >
                                    <Button
                                        type="submit"
                                        variant="danger"
                                        size="sm"
                                    >
                                        Cerrar sesión
                                    </Button>
                                </form>
                            </>
                        ) : (
                            <Link href="/login">
                                <Button size="sm">Iniciar sesión</Button>
                            </Link>
                        )}
                    </div>

                    <button
                        type="button"
                        className="sm:hidden inline-flex items-center justify-center rounded-md p-2 text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)] transition-colors"
                        onClick={() => setIsOpen((prev) => !prev)}
                        aria-expanded={isOpen}
                    >
                        <span className="sr-only">Abrir menú</span>
                        <svg
                            className={clsx(
                                "h-6 w-6 transition-transform",
                                isOpen && "rotate-90"
                            )}
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            {isOpen && (
                <div className="sm:hidden bg-[var(--color-bg-primary)] border-t border-[var(--color-border-primary)] py-3">
                    <div className="flex flex-col items-center space-y-3 px-4">
                        <Link
                            href="/"
                            className="text-[var(--color-text-primary)] hover:text-[var(--color-primary-600)] transition-colors"
                        >
                            Inicio
                        </Link>
                        <Link
                            href={route("swift-auth.users.index")}
                            className="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors"
                        >
                            Usuarios
                        </Link>
                        <Link
                            href={route("swift-auth.roles.index")}
                            className="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors"
                        >
                            Roles
                        </Link>

                        <div className="w-full border-t border-[var(--color-border-primary)] pt-3 mt-3">
                            <div className="flex items-center justify-center gap-3">
                                <ThemeToggle />
                                {user ? (
                                    <>
                                        <Text size="sm" tone="secondary">
                                            {user.name}
                                        </Text>
                                        <form
                                            method="GET"
                                            action={route("swift-auth.logout")}
                                        >
                                            <Button
                                                type="submit"
                                                variant="danger"
                                                size="sm"
                                            >
                                                Salir
                                            </Button>
                                        </form>
                                    </>
                                ) : (
                                    <Link href="/login">
                                        <Button size="sm">
                                            Iniciar sesión
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </nav>
    );
};
