import { formatCurrency } from "../../utils/formatters";

export const ReceiptResult = ({ sale, onClose }) => sale ? (
    <div className="receipt-layer" role="dialog" aria-modal="true" aria-labelledby="receipt-title">
        <section className="receipt-result">
            <div className="receipt-check" aria-hidden="true">✓</div>
            <span className="pos-eyebrow">Pago aprobado</span>
            <h2 id="receipt-title">Venta completada</h2>
            <p>Folio <strong>{sale.folio}</strong></p>
            <strong className="receipt-total">{formatCurrency(sale.total_net ?? sale.total ?? 0)}</strong>
            <div className="receipt-actions"><button type="button" className="secondary-action" onClick={() => window.print()}>Imprimir recibo</button><button type="button" className="checkout-button" onClick={onClose}>Nueva venta</button></div>
        </section>
    </div>
) : null;
