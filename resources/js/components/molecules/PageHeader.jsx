export const PageHeader = ({ title, description, actions }) => (
    <header className="page-heading">
        <div>
            <h1>{title}</h1>
            {description && <p>{description}</p>}
        </div>
        {actions && <div className="page-actions">{actions}</div>}
    </header>
);
