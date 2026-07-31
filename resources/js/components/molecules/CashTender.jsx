import { formatCurrency } from "../../utils/formatters";

export const CashTender = ({ total, value, onChange }) => {
    const received = Number(value || 0);
    return <div className="cash-tender">
        <label htmlFor="cash-received">Monto recibido</label>
        <div className="money-input"><span>$</span><input id="cash-received" type="number" min={total} step="0.01" inputMode="decimal" value={value} onChange={(e) => onChange(e.target.value)} /></div>
        <div className="cash-quick">{[total, Math.ceil(total / 50) * 50, Math.ceil(total / 100) * 100].filter((v, i, a) => v > 0 && a.indexOf(v) === i).map(v => <button type="button" key={v} onClick={() => onChange(String(v))}>{formatCurrency(v)}</button>)}</div>
        <p className="cash-change">Cambio <strong>{formatCurrency(Math.max(0, received - total))}</strong></p>
    </div>;
};
