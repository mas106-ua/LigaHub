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
  } catch {
    return iso;
  }
};

export default function MatchRow({ m }) {
  const isPlayed = m.status === "played";
  const score = isPlayed && m.score ? `${m.score.home} - ${m.score.away}` : "—";
  const venue = m.venue ? ` · ${m.venue}` : "";

  return (
    <li className="list-group-item match-row">
      <div className="match-row__main">
        <div className="match-row__meta">
          {fmt(m.scheduled_at)}
          {venue}
        </div>

        <div className="match-row__teams">
          <span className="match-row__team">
            {m.home_team?.name || `Equipo ${m.home_team?.id}`}
          </span>

          <span className="match-row__vs">vs</span>

          <span className="match-row__team">
            {m.away_team?.name || `Equipo ${m.away_team?.id}`}
          </span>
        </div>
      </div>

      <div className="match-row__aside">
        <div className="match-row__score">{score}</div>
        <MatchStatusBadge status={m.status} />
      </div>
    </li>
  );
}