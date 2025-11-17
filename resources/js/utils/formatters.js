import { format } from 'date-fns';
import { es } from 'date-fns/locale';

export const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(Number(value || 0));

export const formatDate = (date) => format(new Date(date), 'PP', { locale: es });
