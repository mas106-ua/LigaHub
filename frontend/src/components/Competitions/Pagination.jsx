export default function Pagination({ page, total, perPage, onPage }) {
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  if (totalPages <= 1) return null;

  const currentPage = Math.min(Math.max(1, page), totalPages);
  const from = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
  const to = Math.min(total, currentPage * perPage);

  const go = (targetPage) => () => {
    onPage(Math.min(Math.max(1, targetPage), totalPages));
  };

  const around = 2;
  const start = Math.max(1, currentPage - around);
  const end = Math.min(totalPages, currentPage + around);
  const pages = [];

  for (let p = start; p <= end; p += 1) {
    pages.push(p);
  }

  return (
    <nav className="app-pagination-wrapper mt-3" aria-label="Paginación">
      <div className="app-pagination__summary">
        Mostrando {from}-{to} de {total}
      </div>

      <ul className="pagination app-pagination mb-0">
        <li className={`page-item ${currentPage <= 1 ? "disabled" : ""}`}>
          <button
            type="button"
            className="page-link"
            onClick={go(currentPage - 1)}
            disabled={currentPage <= 1}
          >
            Anterior
          </button>
        </li>

        {start > 1 && (
          <>
            <li className="page-item">
              <button type="button" className="page-link" onClick={go(1)}>
                1
              </button>
            </li>
            {start > 2 && (
              <li className="page-item disabled">
                <span className="page-link">…</span>
              </li>
            )}
          </>
        )}

        {pages.map((p) => (
          <li key={p} className={`page-item ${p === currentPage ? "active" : ""}`}>
            <button
              type="button"
              className="page-link"
              onClick={go(p)}
              aria-current={p === currentPage ? "page" : undefined}
            >
              {p}
            </button>
          </li>
        ))}

        {end < totalPages && (
          <>
            {end < totalPages - 1 && (
              <li className="page-item disabled">
                <span className="page-link">…</span>
              </li>
            )}
            <li className="page-item">
              <button
                type="button"
                className="page-link"
                onClick={go(totalPages)}
              >
                {totalPages}
              </button>
            </li>
          </>
        )}

        <li className={`page-item ${currentPage >= totalPages ? "disabled" : ""}`}>
          <button
            type="button"
            className="page-link"
            onClick={go(currentPage + 1)}
            disabled={currentPage >= totalPages}
          >
            Siguiente
          </button>
        </li>
      </ul>
    </nav>
  );
}