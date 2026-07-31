import { useEffect, useRef } from "react";

export const ScanInput = ({ value, onChange, onSubmit, disabled }) => {
    const inputRef = useRef(null);
    useEffect(() => { if (!disabled) inputRef.current?.focus(); }, [disabled]);
    return (
        <form className="scan-input" onSubmit={(event) => { event.preventDefault(); onSubmit?.(); }} role="search">
            <span className="scan-icon" aria-hidden="true">⌁</span>
            <label className="sr-only" htmlFor="pos-product-search">Escanear o buscar producto</label>
            <input ref={inputRef} id="pos-product-search" value={value} disabled={disabled}
                onChange={(event) => onChange(event.target.value)}
                placeholder="Escanea o busca por nombre o SKU" autoComplete="off" inputMode="search" />
            {value && <button type="button" className="icon-button" onClick={() => onChange("")} aria-label="Limpiar búsqueda">×</button>}
        </form>
    );
};
