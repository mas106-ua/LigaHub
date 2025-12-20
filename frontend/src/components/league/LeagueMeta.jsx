export default function LeagueMeta({ league, detail, compact = false }) {
  const src = league || detail || {};

  const seasonCode =
    src.season?.code ||
    src.season_code ||
    "";
  const regionName =
    src.region?.name ||
    src.region_name ||
    src.region_code ||
    "";
  const categoryName =
    src.category?.name ||
    src.category_name ||
    "";
  const categoryLevel =
    src.category?.level ||
    src.category_level ||
    "";
  const categoryGender =
    src.category?.gender ||
    src.category_gender ||
    "";

  if (!seasonCode && !regionName && !categoryName) {
    return null;
  }

  // Versión compacta: para tarjetas (CompetitionCard, CompetitionCardWide)
  if (compact) {
    return (
      <div className="text-muted small">
        {seasonCode && (
          <span className="me-3">
            <strong>Temporada:</strong> {seasonCode}
          </span>
        )}

        {regionName && (
          <span className="me-3">
            <strong>CCAA:</strong> {regionName}
          </span>
        )}

        {categoryName && (
          <span>
            <strong>Categoría:</strong> {categoryName}
          </span>
        )}
      </div>
    );
  }

  // Versión normal: para cabeceras de liga
  return (
    <div className="text-muted">
      {seasonCode && <>Temporada {seasonCode}</>}
      {seasonCode && categoryName && " · "}
      {categoryName && <>{categoryName}</>}
      {regionName && <> · {regionName}</>}
    </div>
  );
}
