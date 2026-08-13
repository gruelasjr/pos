import { formatCurrency } from "../../utils/formatters";

export const CartLine = ({ item, busy, onChange, onDiscount, onRemove }) => (
    <article className="cart-line">
        <div className="cart-line__body">
            <strong>{item.product.short_description}</strong>
            <small>SKU {item.product.sku} · {formatCurrency(item.unit_price)} c/u</small>
        </div>
        <div className="quantity-control" aria-label={`Cantidad de ${item.product.short_description}`}>
            <button type="button" disabled={busy || Number(item.quantity) <= 1} onClick={() => onChange(Number(item.quantity) - 1)} aria-label="Restar uno">−</button>
            <output aria-label="Cantidad">{item.quantity}</output>
            <button type="button" disabled={busy} onClick={() => onChange(Number(item.quantity) + 1)} aria-label="Agregar uno">+</button>
        </div>
        <strong className="cart-line__subtotal">{formatCurrency(item.subtotal)}</strong>
        <label className="line-discount">Descuento<input type="number" min="0" step="0.01" value={item.discount || ""} onChange={event => onDiscount(event.target.value)} aria-label={`Descuento de ${item.product.short_description}`}/></label>
        <button type="button" className="remove-button" disabled={busy} onClick={onRemove} aria-label={`Eliminar ${item.product.short_description}`}>Eliminar</button>
    </article>
);
