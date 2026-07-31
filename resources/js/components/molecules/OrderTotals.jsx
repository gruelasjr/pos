import { formatCurrency } from "../../utils/formatters";

export const OrderTotals = ({ cart }) => (
    <dl className="order-totals">
        <div><dt>Subtotal</dt><dd>{formatCurrency(cart?.subtotal ?? cart?.total_gross ?? cart?.total_net ?? 0)}</dd></div>
        <div><dt>Descuento</dt><dd>− {formatCurrency(cart?.discount_total ?? 0)}</dd></div>
        <div className="order-totals__total"><dt>Total</dt><dd>{formatCurrency(cart?.total_net ?? 0)}</dd></div>
    </dl>
);
