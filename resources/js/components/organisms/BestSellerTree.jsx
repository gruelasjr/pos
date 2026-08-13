import { useState } from "react";
import { ChevronDown, ChevronRight, Image as ImageIcon } from "lucide-react";
import { formatCurrency } from "../../utils/formatters";

const ProductRow = ({ node, depth }) => (
    <div className="tree-row" role="row">
        <div className="tree-product" role="cell" style={{ paddingLeft: depth * 20 }}>
            {node.photo_url ? <img src={node.photo_url} alt="" /> : <span className="product-thumb tree-placeholder"><ImageIcon size={16}/></span>}
            <span><strong>{node.name}</strong><small>{node.sku}</small></span>
        </div>
        <span className="tree-number" role="cell">{node.units}</span>
        <span className="tree-number" role="cell">{formatCurrency(node.net_sales)}</span>
        <span className="tree-number" role="cell">{node.tickets}</span>
        <span className="tree-number" role="cell">{node.stock}</span>
    </div>
);

const TreeNode = ({ node, depth = 0 }) => {
    const [open, setOpen] = useState(depth < 1);
    if (node.type === "product") return <ProductRow node={node} depth={depth}/>;
    return <>
        <div className="tree-row tree-row--group" role="row">
            <div className="tree-label" role="cell" style={{ paddingLeft: depth * 20 }}>
                <button className="tree-toggle" type="button" aria-expanded={open} onClick={() => setOpen(value => !value)}>{open ? <ChevronDown size={17}/> : <ChevronRight size={17}/>}<span className="sr-only">{open ? "Contraer" : "Expandir"} {node.label}</span></button>
                <span>{node.label}</span>
            </div>
            <span className="tree-number" role="cell">{node.units}</span>
            <span className="tree-number" role="cell">{formatCurrency(node.net_sales)}</span>
            <span className="tree-number" role="cell">{node.tickets}</span>
            <span className="tree-number" role="cell">{node.stock}</span>
        </div>
        {open && node.children?.map(child => <TreeNode key={child.key || child.id} node={child} depth={depth + 1}/>)}
    </>;
};

export const BestSellerTree = ({ nodes = [] }) => (
    <div className="tree-table" role="table" aria-label="Jerarquía de productos más vendidos">
        <div className="tree-row tree-head" role="row"><span role="columnheader">Agrupación / producto</span><span>Unidades</span><span>Venta neta</span><span>Tickets</span><span>Stock</span></div>
        {nodes.length ? nodes.map(node => <TreeNode key={node.key || node.id} node={node}/>) : <div className="empty-state">Sin ventas para estos filtros.</div>}
    </div>
);
