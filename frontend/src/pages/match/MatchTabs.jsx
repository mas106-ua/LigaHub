import { NavLink } from "react-router-dom";

export default function MatchTabs({ base, centered=false }) {
  const tabs = [
    { to: `${base}/eventos`,      label: "Eventos" },
    { to: `${base}/alineaciones`, label: "Alineaciones" },
    { to: `${base}/estadisticas`, label: "Estadísticas" },
  ];
  return (
    <ul className={"nav nav-tabs match-tabs" + (centered ? " justify-content-center" : "")}>
      {tabs.map(t => (
        <li className="nav-item" key={t.to}>
          <NavLink className={({isActive}) => "nav-link" + (isActive ? " active" : "")} to={t.to}>
            {t.label}
          </NavLink>
        </li>
      ))}
    </ul>
  );
}
