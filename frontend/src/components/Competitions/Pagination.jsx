export default function Pagination({ page, total, perPage, onPage }) {
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  if (totalPages <= 1) return null;

  const go = (p) => (e) => { e.preventDefault(); onPage(Math.min(Math.max(1, p), totalPages)); };

  const around = 2;
  const start = Math.max(1, page - around);
  const end = Math.min(totalPages, page + around);
  const pages = [];
  for (let p = start; p <= end; p++) pages.push(p);

  return (
    <nav className="d-flex justify-content-center mt-3">
      <ul className="pagination mb-0">
        <li className={`page-item ${page <= 1 ? "disabled" : ""}`}>
          <a href="#" className="page-link" onClick={go(page - 1)}>Anterior</a>
        </li>

        {start > 1 && (
          <>
            <li className="page-item"><a href="#" className="page-link" onClick={go(1)}>1</a></li>
            {start > 2 && <li className="page-item disabled"><span className="page-link">…</span></li>}
          </>
        )}

        {pages.map((p) => (
          <li key={p} className={`page-item ${p === page ? "active" : ""}`}>
            <a href="#" className="page-link" onClick={go(p)}>{p}</a>
          </li>
        ))}

        {end < totalPages && (
          <>
            {end < totalPages - 1 && <li className="page-item disabled"><span className="page-link">…</span></li>}
            <li className="page-item"><a href="#" className="page-link" onClick={go(totalPages)}>{totalPages}</a></li>
          </>
        )}

        <li className={`page-item ${page >= totalPages ? "disabled" : ""}`}>
          <a href="#" className="page-link" onClick={go(page + 1)}>Siguiente</a>
        </li>
      </ul>
    </nav>
  );
}
