import { useOutletContext, Link } from "react-router-dom";
import { useAuth } from "../../context/AuthContext";

export default function EventsTab() {
  const { match } = useOutletContext();
  const { user } = useAuth();

  const events = Array.isArray(match.events) ? [...match.events] : [];

  // Orden por minuto (null al final) y por id
  events.sort((a, b) => {
    const ma = a.minute ?? 9999;
    const mb = b.minute ?? 9999;
    if (ma !== mb) return ma - mb;
    return (a.id || 0) - (b.id || 0);
  });

  const sections = {
    goals: events.filter((e) => e.type === "goal" || e.type === "own_goal"),
    cards: events.filter((e) => e.type === "yellow" || e.type === "red"),
    subs: events.filter((e) => e.type === "sub_in"), // cambios
    others: events.filter(
      (e) =>
        !["goal", "own_goal", "yellow", "red", "sub_in", "sub_out"].includes(
          e.type
        )
    ),
  };

  const renderRow = (e) => {
    const isSub = e.type === "sub_in";

    const mainName = e.player?.name ?? e.player_name ?? "";
    const relatedName = e.related_player?.name ?? e.related_player_name ?? "";
    const detail = e.detail ?? e.description ?? "";

    const normalText = (
      <>
        {mainName}
        {detail && <span className="text-muted"> · {detail}</span>}
      </>
    );

    return (
      <div key={`ev-${e.id}`} className="event-row">
        {/* LADO LOCAL */}
        <div
          className={
            "event-side event-left" +
            (e.side === "home" ? "" : " event-empty")
          }
        >
          {e.side === "home" && (
            <>
              {isSub ? (
                <>
                  {/* Entra */}
                  <span className="badge badge-team-home me-2">Entra</span>
                  <span className="event-text">
                    {mainName}
                    {/* Sale */}
                    {relatedName && (
                      <>
                        <span className="badge badge-team-home ms-3 me-2">
                          Sale
                        </span>
                        {relatedName}
                      </>
                    )}
                    {/* Detalle */}
                    {detail && (
                      <span className="text-muted ms-2">· {detail}</span>
                    )}
                  </span>
                </>
              ) : (
                <>
                  <span className="badge badge-team-home me-2">
                    {label(e.type)}
                  </span>
                  <span className="event-text">{normalText}</span>
                </>
              )}
            </>
          )}
        </div>

        {/* MINUTO */}
        <div className="event-minute">
          <span className="badge-minute">
            {e.minute != null ? `${e.minute}'` : "—"}
          </span>
        </div>

        {/* LADO VISITANTE */}
        <div
          className={
            "event-side event-right" +
            (e.side === "away" ? "" : " event-empty")
          }
        >
          {e.side === "away" && (
            <>
              {isSub ? (
                <span className="event-text">
                  <span className="badge badge-team-away me-2">Entra</span>
                  {mainName}
                  {relatedName && (
                    <>
                      <span className="badge bg-dark ms-3 me-2">Sale</span>
                      {relatedName}
                    </>
                  )}
                  {detail && (
                    <span className="text-muted ms-2">· {detail}</span>
                  )}
                </span>
              ) : (
                <>
                  <span className="event-text">{normalText}</span>
                  <span className="badge badge-team-away ms-2">
                    {label(e.type)}
                  </span>
                </>
              )}
            </>
          )}
        </div>
      </div>
    );
  };

  const canManage =
    user && (user.role === "admin" || user.role === "superadmin");

  const hasEvents = events.length > 0;

  return (
    <div className="events-sections">
      {/* Botón para ir a la UI de administración de eventos */}
      {canManage && (
        <div className="d-flex justify-content-end mb-3">
          <Link
            to={`/admin/partidos/${match.id}/eventos`}
            className="btn btn-sm btn-outline-secondary"
          >
            Gestionar eventos
          </Link>
        </div>
      )}

      {!hasEvents && (
        <div className="text-muted">No hay eventos para este partido.</div>
      )}

      {hasEvents && (
        <>
          {sections.goals.length > 0 && (
            <section className="card mb-3">
              <div className="card-header">
                <h6 className="m-0">Goles</h6>
              </div>
              <div className="list-body">{sections.goals.map(renderRow)}</div>
            </section>
          )}

          {sections.cards.length > 0 && (
            <section className="card mb-3">
              <div className="card-header">
                <h6 className="m-0">Tarjetas</h6>
              </div>
              <div className="list-body">{sections.cards.map(renderRow)}</div>
            </section>
          )}

          {sections.subs.length > 0 && (
            <section className="card mb-3">
              <div className="card-header">
                <h6 className="m-0">Sustituciones</h6>
              </div>
              <div className="list-body">{sections.subs.map(renderRow)}</div>
            </section>
          )}

          {sections.others.length > 0 && (
            <section className="card mb-3">
              <div className="card-header">
                <h6 className="m-0">Otros</h6>
              </div>
              <div className="list-body">{sections.others.map(renderRow)}</div>
            </section>
          )}
        </>
      )}
    </div>
  );
}

function label(t) {
  return (
    {
      goal: "Gol",
      own_goal: "Gol p.p.",
      yellow: "Amarilla",
      red: "Roja",
      sub_in: "Entra",
      sub_out: "Sale",
    }[t] || t
  );
}
