import { useMemo } from "react";
import { Link, useSearchParams } from "react-router-dom";

export default function SiblingsTabs({ currentId, siblings }) {
  const [sp] = useSearchParams();
  const search = useMemo(() => {
    const entries = Array.from(sp.entries());
    return entries.length ? `?${entries.map(([k,v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join("&")}` : "";
  }, [sp]);

  if (!siblings || siblings.length <= 1) return null;

  return (
    <ul className="nav nav-pills mb-3">
      {siblings.map((s) => (
        <li className="nav-item" key={s.id}>
          <Link
            className={`nav-link ${String(s.id) === String(currentId) ? "active" : ""}`}
            to={`/comp/${s.id}/jornadas${search}`}
          >
            {/* Si tiene número, úsalo, si no, muestra el nombre completo */}
            {s.groupNumber ? `Grupo ${s.groupNumber}` : s.name}
          </Link>
        </li>
      ))}
    </ul>
  );
}
