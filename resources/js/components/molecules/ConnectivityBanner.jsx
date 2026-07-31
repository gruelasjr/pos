export const ConnectivityBanner = ({ online }) => !online ? (
    <div className="connectivity-banner" role="status" aria-live="polite">
        <span aria-hidden="true">●</span>
        Sin conexión · tu carrito se guarda en este dispositivo. Conéctate para cobrar.
    </div>
) : null;
