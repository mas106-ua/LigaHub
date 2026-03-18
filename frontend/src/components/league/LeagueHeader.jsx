import { NavLink } from "react-router-dom";
import LeagueMeta from "./LeagueMeta";

/**
 * detail = lo que devuelve /api/leagues/{id}/detail (data)
 * active = "resumen" | "jornadas" | "clasificacion" | "equipos" | "estadisticas"
 */
export default function LeagueHeader({ detail, active }) {
  if (!detail) return null;

  const leagueId = detail.id;
  const title = detail.name || "Liga";

  return (
    <header className="app-league-header mb-4">
      <div className="app-league-header__top">
        <div className="app-league-header__summary">
          <h2 className="h3 app-league-header__title">{title}</h2>
          <LeagueMeta detail={detail} />
        </div>
      </div>

      <nav className="app-tabs-scroller border-bottom" aria-label="Secciones de la liga">
        <ul className="nav nav-tabs app-scroll-tabs app-scroll-tabs--underline mb-0">
          <li className="nav-item flex-shrink-0">
            <NavLink
              end
              to={`/comp/${leagueId}`}
              className={({ isActive }) =>
                `nav-link ${isActive || active === "resumen" ? "active" : ""}`.trim()
              }
            >
              Resumen
            </NavLink>
          </li>

          <li className="nav-item flex-shrink-0">
            <NavLink
              to={`/comp/${leagueId}/jornadas`}
              className={({ isActive }) =>
                `nav-link ${isActive || active === "jornadas" ? "active" : ""}`.trim()
              }
            >
              Jornadas
            </NavLink>
          </li>

          <li className="nav-item flex-shrink-0">
            <NavLink
              to={`/comp/${leagueId}/clasificacion`}
              className={({ isActive }) =>
                `nav-link ${
                  isActive || active === "clasificacion" ? "active" : ""
                }`.trim()
              }
            >
              Clasificación
            </NavLink>
          </li>

          <li className="nav-item flex-shrink-0">
            <NavLink
              to={`/comp/${leagueId}/equipos`}
              className={({ isActive }) =>
                `nav-link ${isActive || active === "equipos" ? "active" : ""}`.trim()
              }
            >
              Equipos
            </NavLink>
          </li>

          <li className="nav-item flex-shrink-0">
            <NavLink
              to={`/comp/${leagueId}/estadisticas`}
              className={({ isActive }) =>
                `nav-link ${
                  isActive || active === "estadisticas" ? "active" : ""
                }`.trim()
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