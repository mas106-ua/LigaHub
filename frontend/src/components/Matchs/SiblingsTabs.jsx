import { useMemo } from "react";
import { Link, useSearchParams } from "react-router-dom";

export default function SiblingsTabs({ currentId, siblings }) {
  const [sp] = useSearchParams();

  const search = useMemo(() => {
    const entries = Array.from(sp.entries());
    return entries.length
      ? `?${entries
          .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
          .join("&")}`
      : "";
  }, [sp]);

  if (!siblings || siblings.length <= 1) return null;

  return (
    <nav className="app-tabs-scroller mb-3" aria-label="Grupos de la competición">
      <ul className="nav nav-pills app-scroll-tabs mb-0">
        {siblings.map((s) => (
          <li className="nav-item flex-shrink-0" key={s.id}>
            <Link
              className={`nav-link ${
                String(s.id) === String(currentId) ? "active" : ""
              }`}
              to={`/comp/${s.id}/jornadas${search}`}
            >
              {s.groupNumber ? `Grupo ${s.groupNumber}` : s.name}
            </Link>
          </li>
        ))}
      </ul>
    </nav>
  );
}