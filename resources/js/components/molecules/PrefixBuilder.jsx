import { ArrowDown, ArrowUp, GripVertical, Plus, Trash2 } from "lucide-react";
import { IconButton, Input, Select } from "../atoms";

const codedValues = definition => definition?.coded_values || definition?.codedValues || [];

export const PrefixBuilder = ({ definitions, value, onChange, disabled = false }) => {
    const eligible = definitions.filter(definition => definition.active !== false && ["text", "select"].includes(definition.type));
    const used = new Set(value.map(segment => segment.definition_id));
    const move = (index, direction) => {
        const nextIndex = index + direction;
        if (nextIndex < 0 || nextIndex >= value.length) return;
        const next = [...value];
        [next[index], next[nextIndex]] = [next[nextIndex], next[index]];
        onChange(next);
    };
    const updateDefinition = (index, definitionId) => {
        const definition = eligible.find(item => item.id === definitionId);
        const coded = codedValues(definition).find(item => item.active !== false);
        const next = [...value];
        next[index] = { definition_id: definitionId, coded_value_id: coded?.id || "" };
        onChange(next);
    };
    const add = () => {
        const definition = eligible.find(item => !used.has(item.id) && codedValues(item).some(coded => coded.active !== false));
        if (!definition) return;
        const coded = codedValues(definition).find(item => item.active !== false);
        onChange([...value, { definition_id: definition.id, coded_value_id: coded.id }]);
    };

    return <section className="prefix-builder" aria-labelledby="prefix-builder-title">
        <div className="prefix-builder__heading"><div><h3 id="prefix-builder-title">Composición del prefijo</h3><p>Ordena los metadatos; los productos con este SKU heredarán sus valores.</p></div></div>
        <div className="prefix-segments">
            {value.map((segment, index) => {
                const definition = eligible.find(item => item.id === segment.definition_id);
                const coded = codedValues(definition).find(item => item.id === segment.coded_value_id);
                return <div className="prefix-segment" key={`${segment.definition_id}-${index}`}>
                    <GripVertical className="prefix-grip" size={18} aria-hidden="true"/>
                    <span className="prefix-order" aria-label={`Posición ${index + 1}`}>{index + 1}</span>
                    <label>Metadata<Select disabled={disabled} value={segment.definition_id} onChange={event => updateDefinition(index, event.target.value)}>{eligible.map(item => <option disabled={used.has(item.id) && item.id !== segment.definition_id} value={item.id} key={item.id}>{item.label}</option>)}</Select></label>
                    <label>Valor<Select disabled={disabled} value={segment.coded_value_id} onChange={event => onChange(value.map((item, current) => current === index ? { ...item, coded_value_id: event.target.value } : item))}>{codedValues(definition).filter(item => item.active !== false || item.id === segment.coded_value_id).map(item => <option value={item.id} key={item.id}>{item.value}</option>)}</Select></label>
                    <label className="prefix-code">Código<Input value={coded?.code || ""} disabled readOnly/></label>
                    <div className="prefix-actions">
                        <IconButton size="sm" variant="ghost" disabled={disabled || index === 0} onClick={() => move(index, -1)} label={`Subir ${definition?.label || "metadata"}`}><ArrowUp size={15}/></IconButton>
                        <IconButton size="sm" variant="ghost" disabled={disabled || index === value.length - 1} onClick={() => move(index, 1)} label={`Bajar ${definition?.label || "metadata"}`}><ArrowDown size={15}/></IconButton>
                        <IconButton size="sm" variant="ghost" disabled={disabled} onClick={() => onChange(value.filter((_, current) => current !== index))} label={`Quitar ${definition?.label || "metadata"}`}><Trash2 size={15}/></IconButton>
                    </div>
                </div>;
            })}
        </div>
        {!disabled && <button className="prefix-add" type="button" onClick={add} disabled={eligible.every(item => used.has(item.id) || !codedValues(item).some(coded => coded.active !== false))}><Plus size={16}/>Agregar metadata</button>}
    </section>;
};
