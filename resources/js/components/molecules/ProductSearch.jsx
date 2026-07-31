import { formatCurrency } from "../../utils/formatters";

export const ProductSearch = ({ products, query, loading, onSelect }) => (
    <section className="product-results" aria-label="Resultados de productos" aria-live="polite">
        {loading && <p className="pos-muted">Buscando productos…</p>}
        {!loading && query && products.length === 0 && <p className="pos-empty">No encontramos productos para “{query}”.</p>}
        {products.map((product) => (
            <button type="button" className="product-result" key={product.id} onClick={() => onSelect(product.id)}>
                <span><strong>{product.short_description}</strong><small>SKU {product.sku}</small></span>
                <span>{formatCurrency(product.sale_price ?? product.price ?? 0)}</span>
            </button>
        ))}
    </section>
);
