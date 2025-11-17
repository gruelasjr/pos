const DataTable = ({ columns, data, emptyMessage = 'Sin datos' }) => (
    <div className="overflow-x-auto bg-white rounded-xl border border-slate-100 shadow-sm">
        <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
                <tr>
                    {columns.map((column) => (
                        <th
                            key={column.key}
                            className="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider"
                        >
                            {column.title}
                        </th>
                    ))}
                </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
                {data.length === 0 && (
                    <tr>
                       <td colSpan={columns.length} className="px-4 py-6 text-center text-sm text-slate-500">
                            {emptyMessage}
                        </td>
                    </tr>
                )}
                {data.map((row) => (
                    <tr key={row.id || JSON.stringify(row)}>
                        {columns.map((column) => (
                            <td key={column.key} className="px-4 py-3 text-sm text-slate-700">
                                {typeof column.render === 'function'
                                    ? column.render(row[column.key], row)
                                    : row[column.key]}
                            </td>
                        ))}
                    </tr>
                ))}
            </tbody>
        </table>
    </div>
);

export default DataTable;
