import { ArrowDown, ArrowUp, Plus, X } from "lucide-react";

export const GroupBuilder = ({ value, definitions = [], onChange }) => {
    const options = [{ value: "category", label: "Categoría" }, { value: "tag", label: "Tag" }, ...definitions.map(item => ({ value: `metadata:${item.key}`, label: item.label }))];
    const available = options.filter(option => !value.includes(option.value));
    const move = (index, offset) => { const next = [...value]; const target = index + offset; if (target < 0 || target >= next.length) return; [next[index], next[target]] = [next[target], next[index]]; onChange(next); };
    return <div className="group-builder" aria-label="Orden de agrupaciones">
        {value.map((dimension, index) => <div className="group-chip" key={dimension}>
            <span>{index + 1}. {options.find(option => option.value === dimension)?.label || dimension}</span>
            <button type="button" className="chip-action" disabled={index === 0} onClick={() => move(index, -1)} aria-label="Subir agrupación"><ArrowUp size={14}/></button>
            <button type="button" className="chip-action" disabled={index === value.length - 1} onClick={() => move(index, 1)} aria-label="Bajar agrupación"><ArrowDown size={14}/></button>
            <button type="button" className="chip-action" onClick={() => onChange(value.filter(item => item !== dimension))} aria-label="Quitar agrupación"><X size={14}/></button>
        </div>)}
        {value.length < 3 && available.length > 0 && <label className="group-add"><Plus size={15}/><span className="sr-only">Añadir agrupación</span><select value="" onChange={event => event.target.value && onChange([...value, event.target.value])}><option value="">Agrupar por…</option>{available.map(option => <option key={option.value} value={option.value}>{option.label}</option>)}</select></label>}
    </div>;
};
