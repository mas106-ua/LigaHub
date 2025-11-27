import { NavLink } from "react-router-dom";

/**
 * detail = lo que devuelve /api/leagues/{id}/detail (data)
 * active = "resumen" | "jornadas" | "clasificacion" | "equipos" | "estadisticas"
 */
export default function LeagueHeader({ detail, active }) {
  if (!detail) return null;

  const leagueId   = detail.id;
  const seasonCode = detail.season?.code || "";
  const category   = detail.category?.name || "";
  const title      = detail.name || "Liga";

  return (
    <header className="mb-4">
      {/* TÍTULO + SUBTÍTULO */}
      <div className="mb-3">
        <h2 className="h3 mb-1">{title}</h2>
        <div className="text-muted">
          {seasonCode && <>Temporada {seasonCode}</>}
          {seasonCode && category && " · "}
          {category}
        </div>
      </div>

      {/* TABS DE NAVEGACIÓN */}
      <nav className="border-bottom">
        <ul className="nav nav-tabs">
          <li className="nav-item">
            <NavLink
              end
              to={`/comp/${leagueId}`}
              className={({ isActive }) =>
                "nav-link" + ((isActive || active === "resumen") ? " active" : "")
              }
            >
              Resumen
            </NavLink>
          </li>

          <li className="nav-item">
            <NavLink
              to={`/comp/${leagueId}/jornadas`}
              className={({ isActive }) =>
                "nav-link" + ((isActive || active === "jornadas") ? " active" : "")
              }
            >
              Jornadas
            </NavLink>
          </li>

          <li className="nav-item">
            <NavLink
              to={`/comp/${leagueId}/clasificacion`}
              className={({ isActive }) =>
                "nav-link" + ((isActive || active === "clasificacion") ? " active" : "")
              }
            >
              Clasificación
            </NavLink>
          </li>

          <li className="nav-item">
            <NavLink
              to={`/comp/${leagueId}/equipos`}
              className={({ isActive }) =>
                "nav-link" + ((isActive || active === "equipos") ? " active" : "")
              }
            >
              Equipos
            </NavLink>
          </li>

          <li className="nav-item">
            <NavLink
              to={`/comp/${leagueId}/estadisticas`}
              className={({ isActive }) =>
                "nav-link" + ((isActive || active === "estadisticas") ? " active" : "")
              }
            >
              Estadísticas
            </NavLink>
          </li>
        </ul>
      </nav>
    </header>
  );
}
