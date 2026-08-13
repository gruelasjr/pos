import { Link, router } from "@inertiajs/react";
import clsx from "clsx";
import { BarChart3, Barcode, Boxes, Building2, ClipboardList, LayoutDashboard, LogOut, PackageSearch, PanelLeftClose, PanelLeftOpen, ShoppingBag, Tags, Users, Wifi } from "lucide-react";
import { ThemeToggle } from "../molecules/ThemeToggle";
import { IconButton } from "../atoms";

const navItems = [
    { label: "Inicio", href: "/", icon: LayoutDashboard, roles: ["admin", "auditor", "seller"] },
    { label: "Nueva venta", href: "/pos", icon: Tags, roles: ["admin", "seller"] },
    { label: "Productos", href: "/catalogo/productos", icon: Boxes, roles: ["admin", "auditor", "seller"] },
    { label: "Inventario", href: "/catalogo/inventario", icon: PackageSearch, roles: ["admin", "auditor", "seller"] },
    { label: "Rangos SKU", href: "/catalogo/skus", icon: Barcode, roles: ["admin"] },
    { label: "Almacenes", href: "/catalogo/almacenes", icon: Building2, roles: ["admin"] },
    { label: "Clientes", href: "/clientes", icon: Users, roles: ["admin", "seller"] },
    { label: "Ventas", href: "/ventas", icon: ClipboardList, roles: ["admin", "auditor", "seller"] },
    { label: "Reportes", href: "/reportes", icon: BarChart3, roles: ["admin", "auditor"] },
];

export const normalizedRoles = (user) => (user?.roles || []).map((role) => {
    const value = typeof role === "string" ? role : role.slug || role.name || role.uri_role || "";
    const normalized = String(value).toLowerCase().replace("pos-", "");
    return normalized === "vendedor" ? "seller" : normalized;
});

export const AppSidebar = ({ user, onLogout, collapsed = false, onToggle }) => {
    const pathname = typeof window === "undefined" ? "/" : window.location.pathname;
    const roles = normalizedRoles(user);
    const visibleItems = navItems.filter((item) => !roles.length || item.roles.some((role) => roles.includes(role)));
    const active = [...visibleItems].sort((a, b) => b.href.length - a.href.length)
        .find((item) => item.href === "/" ? pathname === "/" : pathname.startsWith(item.href))?.href;

    return (
        <aside className="app-sidebar" aria-label="Barra lateral" data-collapsed={collapsed}>
            <div className="brand-lockup"><span className="brand-mark"><ShoppingBag size={20}/></span><span className="sidebar-label">POS Faro</span></div>
            <IconButton className="sidebar-collapse" variant="ghost" label={collapsed ? "Expandir navegación" : "Contraer navegación"} aria-expanded={!collapsed} onClick={onToggle}>
                {collapsed ? <PanelLeftOpen size={19}/> : <PanelLeftClose size={19}/>}
            </IconButton>
            <nav className="sidebar-nav" aria-label="Navegación principal">
                {visibleItems.map((item) => {
                    const Icon = item.icon;
                    return <Link key={item.href} href={item.href} className={clsx("sidebar-link", active === item.href && "is-active")}>
                        <Icon size={20} strokeWidth={1.8}/><span className="sidebar-label">{item.label}</span>
                    </Link>;
                })}
            </nav>
            <button className="sidebar-logout" onClick={onLogout} type="button" aria-label="Cerrar sesión"><LogOut size={20}/><span className="sidebar-label">Cerrar sesión</span></button>
        </aside>
    );
};

export const AppHeader = ({ title, user }) => {
    const role = normalizedRoles(user)[0] || "cajero";
    const initials = user?.name?.split(" ").map((part) => part[0]).slice(0, 2).join("").toUpperCase() || "U";
    return (
        <header className="app-topbar">
            <div className="mobile-brand"><span className="brand-mark"><ShoppingBag size={18}/></span><strong>POS Faro</strong></div>
            <div className="topbar-title">{title}</div>
            <div className="topbar-actions">
                <span className="connection-status"><Wifi size={15}/>Conectado</span>
                <ThemeToggle />
                <button className="user-menu" type="button" onClick={() => router.visit("/")} aria-label="Abrir perfil">
                    <span className="user-avatar">{initials}</span>
                    <span className="user-copy"><strong>{user?.name || "Usuario"}</strong><small>{role}</small></span>
                </button>
            </div>
        </header>
    );
};
