import { useOutletContext, Link } from "react-router-dom";

export default function OverviewTab() {
  const { match } = useOutletContext();
  const hasEvents = Array.isArray(match.events) && match.events.length > 0;

  return (
    <div className="card">
      <div className="card-body">
        <p className="mb-2"><strong>Jornada:</strong> {match.matchday}</p>
        {match.venue && <p className="mb-2"><strong>Estadio:</strong> {match.venue.name}</p>}
        {!hasEvents && <p className="text-muted m-0">Todavía no hay eventos registrados.</p>}
        {hasEvents && (
          <>
            <div className="fw-semibold mb-2">Últimos eventos</div>
            <ul className="list-group">
              {match.events.slice(0,5).map(e => (
                <li className="list-group-item d-flex justify-content-between" key={e.id}>
                  <span>{formatEvent(e)}</span>
                  <span className="text-muted">{e.minute}'</span>
                </li>
              ))}
            </ul>
            <div className="mt-3">
              <Link to="../eventos" className="btn btn-sm btn-outline-primary">Ver todos</Link>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
function formatEvent(e) {
  const who = e.player_name || e.team_name || "";
  const map = { goal: "Gol", own_goal:"Gol en propia", yellow:"Amarilla", red:"Roja", sub_in:"Entra", sub_out:"Sale" };
  return `${map[e.type] ?? e.type}${who ? " · " + who : ""}`;
}
