import { useEffect, useMemo, useState } from "react";
import AppLayout from "../../Layouts/AppLayout";
import useApi from "../../hooks/useApi";
import { useCartDraft } from "../../hooks/useCartDraft";
import { formatCurrency } from "../../utils/formatters";
import { ScanInput } from "../../components/molecules/ScanInput";
import { ProductSearch } from "../../components/molecules/ProductSearch";
import { CartLine } from "../../components/molecules/CartLine";
import { OrderTotals } from "../../components/molecules/OrderTotals";
import { ConnectivityBanner } from "../../components/molecules/ConnectivityBanner";
import { PaymentSheet } from "../../components/organisms/PaymentSheet";
import { ReceiptResult } from "../../components/organisms/ReceiptResult";

const apiItems = (response) => response?.data?.items || response?.data || [];
const apiData = (response) => response?.data || response;
const errorMessage = (error, fallback) => error?.response?.data?.message || error?.message || fallback;

const CartsPage = () => {
    const api = useApi();
    const [online, setOnline] = useState(() => navigator.onLine);
    const [carts, setCarts] = useState([]);
    const [warehouses, setWarehouses] = useState([]);
    const [selectedCart, setSelectedCart] = useState(null);
    const [query, setQuery] = useState("");
    const [products, setProducts] = useState([]);
    const [searching, setSearching] = useState(false);
    const [busyItem, setBusyItem] = useState(null);
    const [paymentOpen, setPaymentOpen] = useState(false);
    const [payment, setPayment] = useState({ payment_method: "cash", received: "" });
    const [sale, setSale] = useState(null);
    const [error, setError] = useState("");
    const [loading, setLoading] = useState(true);
    const { restoredDraft, saveDraft, clearDraft } = useCartDraft(selectedCart?.id);

    const updateCartState = (cart) => {
        setCarts((current) => current.map((item) => item.id === cart.id ? cart : item));
        setSelectedCart(cart);
    };

    const loadCarts = async () => {
        const response = await api.carts.list({ per_page: 50 });
        const items = apiItems(response);
        setCarts(items);
        setSelectedCart((previous) => items.find((item) => item.id === previous?.id) || items[0] || null);
    };

    useEffect(() => {
        const sync = () => setOnline(navigator.onLine);
        window.addEventListener("online", sync); window.addEventListener("offline", sync);
        Promise.all([api.warehouses.list(), api.carts.list({ per_page: 50 })])
            .then(([warehouseResponse, cartsResponse]) => {
                const items = apiItems(cartsResponse);
                setWarehouses(apiItems(warehouseResponse)); setCarts(items); setSelectedCart(items[0] || null);
            }).catch((err) => setError(errorMessage(err, "No pudimos abrir el punto de venta.")))
            .finally(() => setLoading(false));
        return () => { window.removeEventListener("online", sync); window.removeEventListener("offline", sync); };
    }, []);

    useEffect(() => {
        if (!selectedCart) return;
        saveDraft({ cart: selectedCart, payment });
    }, [selectedCart, payment, saveDraft]);

    useEffect(() => {
        if (restoredDraft?.payment) setPayment(restoredDraft.payment);
    }, [restoredDraft]);

    useEffect(() => {
        const timer = setTimeout(async () => {
            if (!query.trim() || !online) { setProducts([]); return; }
            setSearching(true);
            try { setProducts(apiItems(await api.products.list({ query: query.trim(), per_page: 8 }))); }
            catch (err) { setError(errorMessage(err, "No pudimos buscar productos.")); }
            finally { setSearching(false); }
        }, 240);
        return () => clearTimeout(timer);
    }, [query, online]);

    const createCart = async (warehouseId) => {
        if (!online) { setError("Conéctate para abrir una caja nueva."); return; }
        setError("");
        try { const cart = apiData(await api.carts.create({ warehouse_id: warehouseId })); await loadCarts(); setSelectedCart(cart); }
        catch (err) { setError(errorMessage(err, "No pudimos abrir la caja.")); }
    };

    const mutateItem = async (itemId, action) => {
        if (!online) { setError("Los cambios pendientes se conservarán, pero requieren conexión para sincronizarse."); return; }
        setBusyItem(itemId); setError("");
        try { updateCartState(apiData(await action())); }
        catch (err) { setError(errorMessage(err, "No pudimos actualizar el carrito.")); }
        finally { setBusyItem(null); }
    };

    const addItem = (productId) => selectedCart && mutateItem(productId, () => api.carts.addItem(selectedCart.id, { product_id: productId, quantity: 1 })).then(() => { setQuery(""); setProducts([]); });
    const itemCount = useMemo(() => selectedCart?.items?.reduce((sum, item) => sum + Number(item.quantity), 0) || 0, [selectedCart]);

    const checkout = async () => {
        if (!online) return;
        if (payment.payment_method === "cash" && Number(payment.received || 0) < Number(selectedCart.total_net)) {
            setError("El monto recibido debe cubrir el total de la venta."); return;
        }
        setBusyItem("checkout"); setError("");
        try {
            const payload = { payment_method: payment.payment_method, payment_details: payment.payment_method === "cash" ? { received: Number(payment.received), change: Number(payment.received) - Number(selectedCart.total_net) } : null };
            const completed = apiData(await api.carts.checkout(selectedCart.id, payload));
            setSale(completed); setPaymentOpen(false); clearDraft(); setPayment({ payment_method: "cash", received: "" }); await loadCarts();
        } catch (err) { setError(errorMessage(err, "No se completó el cobro. Revisa el estado antes de intentarlo de nuevo.")); }
        finally { setBusyItem(null); }
    };

    return (
        <AppLayout title="Punto de venta" posMode>
            <ConnectivityBanner online={online} />
            <div className="pos-workspace">
                <section className="pos-catalog" aria-label="Catálogo y búsqueda">
                    <header className="pos-section-header"><div><span className="pos-eyebrow">Venta activa</span><h1>Cobro rápido</h1></div><select aria-label="Caja o almacén" value="" onChange={(e) => e.target.value && createCart(e.target.value)}><option value="">+ Abrir caja</option>{warehouses.map(w => <option value={w.id} key={w.id}>{w.name}</option>)}</select></header>
                    <ScanInput value={query} onChange={setQuery} disabled={!selectedCart || loading} onSubmit={() => products[0] && addItem(products[0].id)} />
                    <ProductSearch products={products} query={query} loading={searching} onSelect={addItem} />
                    {!query && <div className="pos-guidance"><div className="scanner-mark" aria-hidden="true">⌁</div><h2>Listo para escanear</h2><p>Escanea el código o busca por nombre. El foco vuelve aquí automáticamente.</p></div>}
                </section>
                <section className="pos-cart" aria-label="Carrito actual">
                    <header className="cart-header"><div><span className="pos-eyebrow">{selectedCart?.warehouse?.name || "Caja sin abrir"}</span><h2>Carrito <span>{itemCount} {itemCount === 1 ? "artículo" : "artículos"}</span></h2></div>{selectedCart && <small>{selectedCart.visual_key}</small>}</header>
                    {error && <div className="pos-error" role="alert">{error}<button type="button" onClick={() => setError("")} aria-label="Cerrar mensaje">×</button></div>}
                    <div className="cart-lines">
                        {loading && <p className="pos-muted">Preparando tu caja…</p>}
                        {!loading && !selectedCart && <div className="pos-empty"><strong>Abre una caja para comenzar</strong><span>Selecciona una sucursal en “Abrir caja”.</span></div>}
                        {selectedCart && !selectedCart.items?.length && <div className="pos-empty"><strong>Tu carrito está vacío</strong><span>Escanea o busca el primer producto.</span></div>}
                        {selectedCart?.items?.map(item => <CartLine key={item.id} item={item} busy={busyItem === item.id} onChange={(quantity) => mutateItem(item.id, () => api.carts.updateItem(selectedCart.id, item.id, { quantity }))} onRemove={() => mutateItem(item.id, () => api.carts.removeItem(selectedCart.id, item.id))} />)}
                    </div>
                    {selectedCart && <footer className="cart-checkout"><OrderTotals cart={selectedCart} /><button className="checkout-button" disabled={!selectedCart.items?.length} onClick={() => { setError(""); setPaymentOpen(true); }}>Cobrar <span>{formatCurrency(selectedCart.total_net)}</span></button></footer>}
                </section>
            </div>
            <PaymentSheet open={paymentOpen} cart={selectedCart} payment={payment} busy={busyItem === "checkout"} online={online} error={error} onChange={(values) => setPayment(p => ({ ...p, ...values }))} onClose={() => setPaymentOpen(false)} onConfirm={checkout} />
            <ReceiptResult sale={sale} onClose={() => setSale(null)} />
        </AppLayout>
    );
};

export default CartsPage;
