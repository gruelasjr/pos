export const esMX = {
    common: {
        loading: "Cargando…", retry: "Reintentar", save: "Guardar", cancel: "Cancelar",
        archive: "Archivar", restore: "Restaurar", empty: "Sin información disponible",
        offline: "Sin conexión", stale: "Los datos pueden estar desactualizados",
    },
    resources: {
        products: "Productos", inventory: "Inventario", warehouses: "Almacenes",
        customers: "Clientes", sales: "Ventas", reports: "Reportes",
    },
};

export const t = path => path.split(".").reduce((value, key) => value?.[key], esMX) || path;
