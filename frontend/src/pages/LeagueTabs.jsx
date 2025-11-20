import { NavLink, Link, useLocation, useParams } from "react-router-dom";

function navClass(isActive) {
  return "nav-link" + (isActive ? " active" : "");
}

export default function LeagueTabs() {
  const { leagueId } = useParams();
  const location = useLocation();

  if (!leagueId) return null;

  return (
    <ul className="nav nav-tabs mb-3">
      {/* Resumen */}
      <li className="nav-item">
        <NavLink
          to={`/comp/${leagueId}`}
          end
          className={({ isActive }) => navClass(isActive)}
        >
          Resumen
        </NavLink>
      </li>

      {/* Jornadas */}
      <li className="nav-item">
        <NavLink
          to={`/comp/${leagueId}/jornadas`}
          className={({ isActive }) => navClass(isActive)}
        >
          Jornadas
        </NavLink>
      </li>

      {/* Clasificación */}
      <li className="nav-item">
        <NavLink
          to={`/comp/${leagueId}/clasificacion`}
          className={({ isActive }) => navClass(isActive)}
        >
          Clasificación
        </NavLink>
      </li>

      {/* Equipos → ancla dentro de Resumen */}
      <li className="nav-item">
        <Link
          to={`/comp/${leagueId}#equipos`}
          className={
            "nav-link" +
            (location.pathname === `/comp/${leagueId}` &&
            location.hash === "#equipos"
              ? " active"
              : "")
          }
        >
          Equipos
        </Link>
      </li>

      {/* Estadísticas */}
      <li className="nav-item">
        <NavLink
          to={`/comp/${leagueId}/estadisticas`}
          className={({ isActive }) => navClass(isActive)}
        >
          Estadísticas
        </NavLink>
      </li>
    </ul>
  );
}
