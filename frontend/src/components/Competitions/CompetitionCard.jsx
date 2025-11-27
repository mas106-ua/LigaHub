export default function CompetitionCard({ league }) {
  const { name, region, season, category } = league;
  return (
    <div className="card h-100 shadow-sm">
      <div className="card-body">
        <h6 className="card-title mb-2">{name}</h6>
        <p className="card-text text-muted small mb-0">
          {season?.code && <span className="me-3">Temporada: {season.code}</span>}
          {region?.name && <span className="me-3">CCAA: {region.name}</span>}
          {category && (
            <span>Cat: {category.name} — {category.level} — {category.gender}</span>
          )}
        </p>
      </div>
    </div>
  );
}
