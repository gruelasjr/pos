import { useEffect, useMemo, useState } from "react";
import { Barcode, Pencil, Plus } from "lucide-react";
import AppLayout from "../../Layouts/AppLayout";
import useApi from "../../hooks/useApi";
import { Button, Checkbox, IconButton, Input, Select } from "../../components/atoms";
import { FormField, PageHeader, PrefixBuilder } from "../../components/molecules";
import { Drawer } from "../../components/organisms";

const items = response => response?.data?.items || [];
const blank = () => ({ segments: [], from: 1, to: 100, active: true });

export default function SkuRanges() {
    const api = useApi();
    const [rows, setRows] = useState([]);
    const [definitions, setDefinitions] = useState([]);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState(blank);
    const [reserve, setReserve] = useState({ quantity: 1, prefix: "" });
    const [result, setResult] = useState([]);
    const [error, setError] = useState("");
    const load = async () => {
        const [ranges, metadata] = await Promise.all([api.skuRanges.list({ status: "all", per_page: 100 }), api.metadataDefinitions.list({ status: "all", per_page: 100 })]);
        setRows(items(ranges));
        setDefinitions(items(metadata));
    };
    useEffect(() => { load(); }, []);

    const activeRanges = rows.filter(row => row.active);
    useEffect(() => {
        if (!reserve.prefix && activeRanges.length) setReserve(current => ({ ...current, prefix: activeRanges[0].composed_prefix }));
    }, [rows]);

    const open = row => {
        setError("");
        setEditing(row || {});
        setForm(row ? {
            segments: (row.segments || []).map(segment => ({ definition_id: segment.definition_id, coded_value_id: segment.coded_value_id })),
            from: row.from, to: row.to, active: row.active,
        } : blank());
    };
    const preview = useMemo(() => {
        const codes = form.segments.map(segment => {
            const definition = definitions.find(item => item.id === segment.definition_id);
            return (definition?.coded_values || definition?.codedValues || []).find(item => item.id === segment.coded_value_id)?.code;
        }).filter(Boolean);
        return codes.length ? `${codes.join("-")}-${String(form.from || 0).padStart(6, "0")}` : "Agrega metadata para construir el SKU";
    }, [form.segments, form.from, definitions]);
    const save = async event => {
        event.preventDefault(); setError("");
        try {
            const payload = { ...form, from: Number(form.from), to: Number(form.to) };
            editing?.id ? await api.skuRanges.update(editing.id, payload) : await api.skuRanges.create(payload);
            setEditing(null); await load();
        } catch (exception) { setError(exception?.response?.data?.message || "Revisa la composición y los límites del rango."); }
    };
    const reserveCodes = async event => {
        event.preventDefault(); setError("");
        try {
            const response = await api.skus.reserve({ quantity: Number(reserve.quantity), prefix: reserve.prefix });
            setResult(response?.data?.skus || []); await load();
        } catch (exception) { setError(exception?.response?.data?.message || "No fue posible reservar los SKU."); }
    };

    return <AppLayout title="Rangos SKU"><div className="page-container">
        <PageHeader title="Rangos SKU" description="Clasifica productos automáticamente desde la composición de su SKU." actions={<Button onClick={() => open(null)}><Plus size={16}/>Nuevo rango</Button>}/>
        {error && <div className="error-banner" role="alert">{error}</div>}
        <CodedValuesCatalog api={api} definitions={definitions} reload={load}/>
        <section className="surface panel-pad sku-reserve"><h2 className="panel-title">Reservar códigos</h2><form className="inline-create" onSubmit={reserveCodes}><Input aria-label="Cantidad" type="number" min="1" max="100" value={reserve.quantity} onChange={event => setReserve({ ...reserve, quantity: event.target.value })}/><Select aria-label="Rango" value={reserve.prefix} onChange={event => setReserve({ ...reserve, prefix: event.target.value })}>{activeRanges.map(row => <option value={row.composed_prefix} key={row.id}>{row.composed_prefix}</option>)}</Select><Button type="submit" disabled={!reserve.prefix}>Reservar</Button></form>{result.length > 0 && <output className="code-output">{result.join(" · ")}</output>}</section>
        <div className="data-surface"><table className="responsive-table"><thead><tr><th>Rango</th><th>Metadata automática</th><th>Usados</th><th>Progreso</th><th>Estado</th><th/></tr></thead><tbody>{rows.map(row => { const used = Math.max(0, (row.used_up_to ?? row.from - 1) - row.from + 1); const total = row.to - row.from + 1; return <tr key={row.id}><td data-label="Rango"><span className="product-cell"><span className="product-thumb thumb-empty"><Barcode size={18}/></span><strong>{row.composed_prefix}-{String(row.from).padStart(6, "0")}–{String(row.to).padStart(6, "0")}</strong></span></td><td data-label="Metadata"><span className="tag-list">{row.segments?.map(segment => <span className="mini-tag" key={segment.id}>{segment.definition?.label}: {segment.coded_value?.value || segment.codedValue?.value}</span>)}</span></td><td data-label="Usados">{used} de {total}</td><td data-label="Progreso"><progress max={total} value={used}/></td><td data-label="Estado"><span className={`status ${row.active ? "status--success" : ""}`}>{row.active ? "Activo" : "Archivado"}</span></td><td><IconButton onClick={() => open(row)} label="Editar rango"><Pencil size={16}/></IconButton></td></tr>; })}</tbody></table>{!rows.length && <div className="empty-state">Aún no hay rangos SKU.</div>}</div>
        <Drawer open={editing !== null} title={editing?.id ? "Editar rango SKU" : "Nuevo rango SKU"} onClose={() => setEditing(null)}><form onSubmit={save}><PrefixBuilder definitions={definitions} value={form.segments} onChange={segments => setForm({ ...form, segments })} disabled={Boolean(editing?.locked)}/><div className="prefix-preview"><span>Vista previa</span><strong>{preview}</strong></div><div className="drawer-grid"><FormField label="Desde" type="number" required min="0" disabled={Boolean(editing?.locked)} value={form.from} onChange={event => setForm({ ...form, from: event.target.value })}/><FormField label="Hasta" type="number" required min={form.from || 0} disabled={Boolean(editing?.locked)} value={form.to} onChange={event => setForm({ ...form, to: event.target.value })}/><Checkbox className="span-2" label="Rango activo" checked={form.active} onChange={event => setForm({ ...form, active: event.target.checked })}/></div>{editing?.locked && <p className="inline-notice">La composición y los límites están bloqueados porque el rango ya fue utilizado.</p>}<div className="drawer-footer"><Button type="button" variant="secondary" onClick={() => setEditing(null)}>Cancelar</Button><Button type="submit" disabled={!form.segments.length}>Guardar rango</Button></div></form></Drawer>
    </div></AppLayout>;
}

function CodedValuesCatalog({ api, definitions, reload }) {
    const eligible = definitions.filter(definition => definition.active !== false && ["text", "select"].includes(definition.type));
    const [definitionId, setDefinitionId] = useState("");
    const [value, setValue] = useState("");
    const [code, setCode] = useState("");
    const selectedId = definitionId || eligible[0]?.id || "";
    const selected = eligible.find(definition => definition.id === selectedId);
    const values = selected?.coded_values || selected?.codedValues || [];
    const add = async () => {
        if (!selectedId || !value || !code) return;
        await api.metadataCodedValues.create(selectedId, { value, code });
        setValue(""); setCode(""); await reload();
    };

    return <section className="surface panel-pad coded-values-catalog">
        <div><h2 className="panel-title">Códigos de metadata</h2><p>Define códigos reutilizables antes de construir un rango.</p></div>
        <div className="metadata-create"><Select aria-label="Metadata" value={selectedId} onChange={event => setDefinitionId(event.target.value)}>{eligible.map(definition => <option value={definition.id} key={definition.id}>{definition.label}</option>)}</Select><Input aria-label="Valor" placeholder="Valor, ej. México" value={value} onChange={event => setValue(event.target.value)}/><Input aria-label="Código" placeholder="Código, ej. MX" maxLength="16" value={code} onChange={event => setCode(event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, ""))}/><Button size="sm" type="button" onClick={add} disabled={!selectedId || !value || !code}>Añadir código</Button></div>
        <div className="coded-value-list">{values.map(item => <span className="taxonomy-item" key={item.id}>{item.value} <strong>{item.code}</strong><button type="button" onClick={async () => { await api.metadataCodedValues.update(item.id, { active: !item.active }); await reload(); }}>{item.active ? "Archivar" : "Restaurar"}</button></span>)}</div>
    </section>;
}
