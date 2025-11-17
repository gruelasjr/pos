const StatCard = ({ label, value, hint, trend }) => (
    <div className="bg-white rounded-xl shadow-sm p-4 border border-slate-100">
        <p className="text-sm text-slate-500">{label}</p>
        <p className="text-2xl font-semibold text-slate-900 mt-2">{value}</p>
        {hint && <p className="text-xs text-slate-400 mt-1">{hint}</p>}
        {trend && (
            <p className="text-xs mt-1 font-medium">
                <span className={trend >= 0 ? 'text-emerald-600' : 'text-rose-600'}>
                    {trend >= 0 ? '+' : ''}
                    {trend}%
                </span>{' '}
                vs periodo anterior
            </p>
        )}
    </div>
);

export default StatCard;
