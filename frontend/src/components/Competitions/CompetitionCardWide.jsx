import { Link } from "react-router-dom";

const isYouth = (league) => {
  const txt = `${league?.category?.name || ""} ${league?.name || ""}`.toLowerCase();
  return /juvenil|división de honor juvenil|liga nacional juvenil/.test(txt);
};

const genderIcon = (league) => {
  // 1) Prioridad: Juvenil → verde
  if (isYouth(league)) return "/img/leagues/logo_j.svg";

  // 2) Si no es juvenil, icono por género
  const g = league?.category?.gender;
  if (g === "female") return "/img/leagues/logo_f.svg";
  if (g === "male")   return "/img/leagues/logo_m.svg";

  // 3) Fallback (desconocido/mixto): usa masculino o el que prefieras
  return "/img/leagues/logo_m.svg";
};

export default function CompetitionCardWide({ league }) {
  // asumo shape de BE-01 (CompetitionResource): id, name, season.code, region.code/name, category.name/level
  const region = league.region?.name || league.region_name || league.region_code || "-";
  const season = league.season?.code || league.season_code || "-";
  const category = league.category?.name || league.category_name || "-";

  return (
    <div className="card shadow-sm">
      <div className="card-body d-flex flex-column flex-lg-row align-items-start gap-3">
        <img
            src={genderIcon(league)}
            alt="Logo género"
            width={48}
            height={48}
            className="rounded"
        />

        <div className="flex-grow-1">
          <h2 className="h5 mb-1">{league.name}</h2>
          <div className="text-muted small">
            <span className="me-3"><strong>Temporada:</strong> {season}</span>
            <span className="me-3"><strong>CCAA:</strong> {region}</span>
            <span className=""><strong>Categoría:</strong> {category}</span>
          </div>
        </div>

        <div className="ms-lg-auto">
          <Link to={`/comp/${league.id}`} className="btn btn-primary">
            Ver liga
          </Link>
        </div>
      </div>
    </div>
  );
}
