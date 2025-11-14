import { useOutletContext } from "react-router-dom";

export default function EventsTab() {
  const { match } = useOutletContext();
  const events = Array.isArray(match.events) ? [...match.events] : [];

  if (!events.length) return <div className="text-muted">No hay eventos para este partido.</div>;

  // Orden por minuto (null al final) y por id
  events.sort((a,b) => {
    const ma = a.minute ?? 9999, mb = b.minute ?? 9999;
    if (ma !== mb) return ma - mb;
    return (a.id || 0) - (b.id || 0);
  });

  const sections = {
    goals:    events.filter(e => e.type === "goal" || e.type === "own_goal"),
    cards:    events.filter(e => e.type === "yellow" || e.type === "red"),
    subs:     events.filter(e => e.type === "sub_in" || e.type === "sub_out"),
    others:   events.filter(e =>
                  !["goal","own_goal","yellow","red","sub_in","sub_out"].includes(e.type)
               ),
  };

  const renderRow = (e) => (
    <div key={`ev-${e.id}`} className="event-row">
      <div className={"event-side event-left" + (e.side === "home" ? "" : " event-empty")}>
        {e.side === "home" && (
          <>
            <span className="badge badge-team-home me-2">{label(e.type)}</span>
            <span className="event-text">
              {e.player_name || ""}
              {e.detail ? <span className="text-muted"> · {e.detail}</span> : null}
            </span>
          </>
        )}
      </div>

      <div className="event-minute">
        <span className="badge-minute">{e.minute != null ? `${e.minute}'` : "—"}</span>
      </div>

      <div className={"event-side event-right" + (e.side === "away" ? "" : " event-empty")}>
        {e.side === "away" && (
          <>
            <span className="event-text">
              {e.player_name || ""}
              {e.detail ? <span className="text-muted"> · {e.detail}</span> : null}
            </span>
            <span className="badge badge-team-away ms-2">{label(e.type)}</span>
          </>
        )}
      </div>
    </div>
  );

  return (
    <div className="events-sections">
      {sections.goals.length > 0 && (
        <section className="card mb-3">
          <div className="card-header">
            <h6 className="m-0">Goles</h6>
          </div>
          <div className="list-body">
            {sections.goals.map(renderRow)}
          </div>
        </section>
      )}

      {sections.cards.length > 0 && (
        <section className="card mb-3">
          <div className="card-header">
            <h6 className="m-0">Tarjetas</h6>
          </div>
          <div className="list-body">
            {sections.cards.map(renderRow)}
          </div>
        </section>
      )}

      {sections.subs.length > 0 && (
        <section className="card mb-3">
          <div className="card-header">
            <h6 className="m-0">Sustituciones</h6>
          </div>
          <div className="list-body">
            {sections.subs.map(renderRow)}
          </div>
        </section>
      )}

      {sections.others.length > 0 && (
        <section className="card mb-3">
          <div className="card-header">
            <h6 className="m-0">Otros</h6>
          </div>
          <div className="list-body">
            {sections.others.map(renderRow)}
          </div>
        </section>
      )}
    </div>
  );
}

function label(t){
  return ({
    goal: "Gol",
    own_goal: "Gol p.p.",
    yellow: "Amarilla",
    red: "Roja",
    sub_in: "Entra",
    sub_out: "Sale",
  }[t] || t);
}
