import MatchStatusBadge from "./MatchStatusBadge";

const fmt = (iso) => {
  if (!iso) return "—";
  try {
    const d = new Date(iso);
    return new Intl.DateTimeFormat("es-ES", {
      weekday: "short",
      day: "2-digit",
      month: "short",
      hour: "2-digit",
      minute: "2-digit",
    }).format(d);
  } catch { return iso; }
};

export default function MatchRow({ m }) {
  const isPlayed = m.status === "played";
  const score = isPlayed && m.score ? `${m.score.home} - ${m.score.away}` : "—";
  const venue = m.venue ? ` · ${m.venue}` : "";
  return (
    <li className="list-group-item d-flex align-items-center justify-content-between">
      <div className="me-3">
        <div className="text-muted small">{fmt(m.scheduled_at)}{venue}</div>
        <div className="fw-medium">
          {m.home_team?.name || `Equipo ${m.home_team?.id}`}{" "}
          <span className="text-muted">vs</span>{" "}
          {m.away_team?.name || `Equipo ${m.away_team?.id}`}
        </div>
      </div>
      <div className="d-flex align-items-center gap-3">
        <div className="fs-5 fw-semibold text-center" style={{ minWidth: 48 }}>{score}</div>
        <MatchStatusBadge status={m.status} />
      </div>
    </li>
  );
}
