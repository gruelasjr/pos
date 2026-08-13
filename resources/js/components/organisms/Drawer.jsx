import { X } from "lucide-react";
import { IconButton } from "../atoms";

export const Drawer = ({ open, title, children, onClose }) => <div className={`drawer-layer ${open ? "is-open" : ""}`} aria-hidden={!open}>
    <button type="button" className="drawer-backdrop" onClick={onClose} aria-label="Cerrar editor"/>
    <aside className="drawer" role="dialog" aria-modal="true" aria-label={title}>
        <header className="drawer-header"><h2>{title}</h2><IconButton onClick={onClose} label="Cerrar"><X size={18}/></IconButton></header>
        {children}
    </aside>
</div>;
