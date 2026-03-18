import { NavLink } from "react-router-dom";

export default function MatchTabs({ base, centered = false }) {
  const tabs = [
    { to: `${base}/eventos`, label: "Eventos" },
    { to: `${base}/alineaciones`, label: "Alineaciones" },
    { to: `${base}/estadisticas`, label: "Estadísticas" },
  ];

  return (
    <nav className="app-tabs-scroller" aria-label="Secciones del partido">
      <ul
        className={
          "nav nav-tabs match-tabs app-scroll-tabs app-scroll-tabs--underline mb-0" +
          (centered ? " justify-content-center" : "")
        }
      >
        {tabs.map((t) => (
          <li className="nav-item flex-shrink-0" key={t.to}>
            <NavLink
              className={({ isActive }) =>
                `nav-link ${isActive ? "active" : ""}`.trim()
              }
              to={t.to}
            >
              {t.label}
            </NavLink>
          </li>
        ))}
      </ul>
    </nav>
  );
}