import { Link, router } from '@inertiajs/react';
import { Avatar, Button, Divider } from '@heroui/react';
import clsx from 'clsx';
import useAuthStore from '../store/authStore';
import { useMemo } from 'react';

const navItems = [
    { label: 'Dashboard', href: '/' },
    { label: 'Productos', href: '/catalogo/productos' },
    { label: 'Almacenes', href: '/catalogo/almacenes' },
    { label: 'POS', href: '/pos' },
    { label: 'Clientes', href: '/clientes' },
    { label: 'Reportes', href: '/reportes' },
];

const AppLayout = ({ title, children }) => {
    const { user, token, logout } = useAuthStore();
    const pathname = typeof window !== 'undefined' ? window.location.pathname : '/';

    const activeItem = useMemo(
        () => navItems.find((item) => pathname.startsWith(item.href))?.href,
        [pathname],
    );

    const handleLogout = () => {
        logout();
        router.visit('/login');
    };

    if (!token && pathname !== '/login') {
        router.visit('/login');
        return null;
    }

    return (
        <div className="min-h-screen bg-slate-100 flex">
            <aside className="w-64 bg-white shadow-sm hidden md:flex flex-col">
                <div className="p-6 text-xl font-semibold text-slate-800">POS Faro</div>
                <nav className="flex-1 px-4 space-y-2">
                    {navItems.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={clsx(
                                'block px-3 py-2 rounded-lg text-sm font-medium',
                                activeItem === item.href
                                    ? 'bg-blue-100 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-100',
                            )}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>
                {user && (
                    <div className="p-4 border-t">
                        <div className="flex items-center gap-3">
                            <Avatar name={user.name} size="sm" />
                            <div>
                                <p className="text-sm font-semibold text-slate-800">{user.name}</p>
                                <p className="text-xs text-slate-500">{user.role}</p>
                            </div>
                        </div>
                        <Button
                            className="mt-4 w-full"
                            size="sm"
                            color="danger"
                            variant="flat"
                            onPress={handleLogout}
                        >
                            Salir
                        </Button>
                    </div>
                )}
            </aside>
            <main className="flex-1">
                <header className="bg-white shadow-sm px-6 py-4 flex items-center justify-between">
                    <div>
                        <p className="text-xs uppercase text-slate-500">Panel</p>
                        <h1 className="text-xl font-semibold text-slate-800">{title}</h1>
                    </div>
                    <div className="flex items-center gap-4">
                        <span className="text-sm text-slate-600 hidden sm:inline">
                            {user?.email}
                        </span>
                        <Divider orientation="vertical" className="hidden sm:block h-6" />
                        <Button size="sm" variant="ghost" onPress={() => router.visit('/pos')}>
                            Ir a cajas
                        </Button>
                    </div>
                </header>
                <section className="p-6">{children}</section>
            </main>
        </div>
    );
};

export default AppLayout;
