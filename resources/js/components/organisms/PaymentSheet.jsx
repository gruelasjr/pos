import { CashTender } from "../molecules/CashTender";
import { OrderTotals } from "../molecules/OrderTotals";

const methods = [{ id: "cash", label: "Efectivo" }, { id: "card", label: "Tarjeta" }, { id: "transfer", label: "Transferencia" }, { id: "mixed", label: "Mixto" }];
export const PaymentSheet = ({ open, cart, payment, busy, online, error, onChange, onClose, onConfirm }) => (
    <div className={`payment-layer ${open ? "is-open" : ""}`} aria-hidden={!open}>
        <button className="payment-backdrop" type="button" tabIndex={open ? 0 : -1} onClick={onClose} aria-label="Cerrar cobro" />
        <section className="payment-sheet" role="dialog" aria-modal="true" aria-labelledby="payment-title">
            <header><div><span className="pos-eyebrow">Finalizar venta</span><h2 id="payment-title">¿Cómo paga el cliente?</h2></div><button type="button" className="icon-button" onClick={onClose} aria-label="Cerrar">×</button></header>
            <div className="payment-methods" role="radiogroup" aria-label="Método de pago">
                {methods.map(method => <button type="button" role="radio" aria-checked={payment.payment_method === method.id} className={payment.payment_method === method.id ? "is-selected" : ""} key={method.id} onClick={() => onChange({ payment_method: method.id })}>{method.label}</button>)}
            </div>
            {payment.payment_method === "cash" && <CashTender total={Number(cart?.total_net || 0)} value={payment.received ?? ""} onChange={(received) => onChange({ received })} />}
            {payment.payment_method === "mixed" && <p className="inline-notice">El desglose de pago mixto se validará antes de confirmar.</p>}
            <OrderTotals cart={cart} />
            {error && <p className="pos-error" role="alert">{error}</p>}
            {!online && <p className="inline-notice" role="status">El cobro requiere conexión. Tu carrito está guardado.</p>}
            <button className="checkout-button" disabled={busy || !online || !cart?.items?.length} type="button" onClick={onConfirm}>{busy ? "Procesando…" : "Confirmar cobro"}</button>
        </section>
    </div>
);
