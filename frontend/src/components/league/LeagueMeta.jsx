export default function LeagueMeta({ league, detail, compact = false }) {
  const src = league || detail || {};

  const seasonCode = src.season?.code || src.season_code || "";
  const regionName = src.region?.name || src.region_name || src.region_code || "";
  const categoryName = src.category?.name || src.category_name || "";

  const items = [
    seasonCode
      ? { label: compact ? "Temporada:" : null, value: seasonCode }
      : null,
    categoryName
      ? { label: compact ? "Categoría:" : null, value: categoryName }
      : null,
    regionName
      ? { label: compact ? "CCAA:" : null, value: regionName }
      : null,
  ].filter(Boolean);

  if (items.length === 0) return null;

  if (compact) {
    return (
      <div className="app-meta-list app-meta-list--compact">
        {items.map((item) => (
          <span
            key={`${item.label || "meta"}-${item.value}`}
            className="app-meta-item"
          >
            {item.label ? <strong>{item.label}</strong> : null}
            <span>{item.value}</span>
          </span>
        ))}
      </div>
    );
  }

  return (
    <div className="app-meta-line">
      {items.map((item, index) => (
        <span key={`${item.value}-${index}`} className="app-meta-line__item">
          {index > 0 ? (
            <span className="app-meta-line__separator" aria-hidden="true">
              ·
            </span>
          ) : null}
          <span>{item.value}</span>
        </span>
      ))}
    </div>
  );
}