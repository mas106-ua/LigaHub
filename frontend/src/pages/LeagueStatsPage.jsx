import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { fetchLeagueStats } from "../api/leagueStats";

function getErrorMessage(error) {
  if (!error) return "Error desconocido";
  const res = error.response;
  if (res?.data?.message) return res.data.message;
  return error.message || "Error de red";
}

export default function LeagueStatsPage() {
  const { leagueId } = useParams();

  const [stats, setStats] = useState(null);
  const [loadingStats, setLoadingStats] = useState(true);
  const [statsError, setStatsError] = useState(null);

  useEffect(() => {
    let cancelled = false;
    setLoadingStats(true);
    setStatsError(null);

    fetchLeagueStats(leagueId)
      .then((data) => {
        if (cancelled) return;
        setStats(data);
      })
      .catch((err) => {
        if (cancelled) return;
        setStatsError(getErrorMessage(err));
      })
      .finally(() => {
        if (!cancelled) setLoadingStats(false);
      });

    return () => {
      cancelled = true;
    };
  }, [leagueId]);

  if (loadingStats) {
    return (
      <div className="container py-4">
        <h3>Estadísticas de la liga</h3>
        <p>Cargando estadísticas...</p>
      </div>
    );
  }

  if (statsError) {
    return (
      <div className="container py-4">
        <h3>Estadísticas de la liga</h3>
        <div className="alert alert-danger">{statsError}</div>
      </div>
    );
  }

  if (!stats) {
    return (
      <div className="container py-4">
        <h3>Estadísticas de la liga</h3>
        <p>No hay datos de estadísticas para esta liga.</p>
      </div>
    );
  }

  const { league, scorers = [], assists = [], cards = [] } = stats;

  return (
    <div className="container py-4">
      <h3 className="mb-3">
        Estadísticas — {league?.name}{" "}
        {league?.season ? `(${league.season})` : null}
      </h3>

      {/* Goleadores */}
      <section className="card mb-3">
        <div className="card-header d-flex justify-content-between align-items-center">
          <h5 className="m-0">Goleadores</h5>
        </div>
        <div className="card-body p-0">
          {scorers.length === 0 ? (
            <div className="p-3 text-muted">No hay goles registrados.</div>
          ) : (
            <div className="table-responsive">
              <table className="table table-sm mb-0">
                <thead>
                  <tr>
                    <th style={{ width: "4rem" }}>Pos</th>
                    <th>Jugador</th>
                    <th>Equipo</th>
                    <th style={{ width: "5rem" }}>Goles</th>
                  </tr>
                </thead>
                <tbody>
                  {scorers.map((row, idx) => (
                    <tr key={`sc-${row.player_id}-${idx}`}>
                      <td>{idx + 1}</td>
                      <td>{row.player_name}</td>
                      <td>{row.team?.short_name || row.team?.name || "—"}</td>
                      <td>
                        {row.goals}
                        {row.delta?.goals ? (
                          <span className="text-muted ms-1">
                            ({row.auto?.goals ?? 0}{" "}
                            {row.delta.goals > 0 ? "+" : ""}
                            {row.delta.goals})
                          </span>
                        ) : null}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </section>

      {/* Asistencias */}
      <section className="card mb-3">
        <div className="card-header d-flex justify-content-between align-items-center">
          <h5 className="m-0">Asistencias</h5>
        </div>
        <div className="card-body p-0">
          {assists.length === 0 ? (
            <div className="p-3 text-muted">No hay asistencias registradas.</div>
          ) : (
            <div className="table-responsive">
              <table className="table table-sm mb-0">
                <thead>
                  <tr>
                    <th style={{ width: "4rem" }}>Pos</th>
                    <th>Jugador</th>
                    <th>Equipo</th>
                    <th style={{ width: "7rem" }}>Asistencias</th>
                  </tr>
                </thead>
                <tbody>
                  {assists.map((row, idx) => (
                    <tr key={`as-${row.player_id}-${idx}`}>
                      <td>{idx + 1}</td>
                      <td>{row.player_name}</td>
                      <td>{row.team?.short_name || row.team?.name || "—"}</td>
                      <td>
                        {row.assists}
                        {row.delta?.assists ? (
                          <span className="text-muted ms-1">
                            ({row.auto?.assists ?? 0}{" "}
                            {row.delta.assists > 0 ? "+" : ""}
                            {row.delta.assists})
                          </span>
                        ) : null}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </section>

      {/* Tarjetas */}
      <section className="card mb-3">
        <div className="card-header d-flex justify-content-between align-items-center">
          <h5 className="m-0">Tarjetas</h5>
        </div>
        <div className="card-body p-0">
          {cards.length === 0 ? (
            <div className="p-3 text-muted">No hay tarjetas registradas.</div>
          ) : (
            <div className="table-responsive">
              <table className="table table-sm mb-0">
                <thead>
                  <tr>
                    <th style={{ width: "4rem" }}>Pos</th>
                    <th>Jugador</th>
                    <th>Equipo</th>
                    <th style={{ width: "5rem" }}>Amarillas</th>
                    <th style={{ width: "5rem" }}>Rojas</th>
                    <th style={{ width: "5rem" }}>Total</th>
                  </tr>
                </thead>
                <tbody>
                  {cards.map((row, idx) => {
                    const total =
                      (row.yellow_cards || 0) + (row.red_cards || 0);
                    return (
                      <tr key={`ca-${row.player_id}-${idx}`}>
                        <td>{idx + 1}</td>
                        <td>{row.player_name}</td>
                        <td>
                          {row.team?.short_name || row.team?.name || "—"}
                        </td>
                        <td>{row.yellow_cards}</td>
                        <td>{row.red_cards}</td>
                        <td>{total}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </section>
    </div>
  );
}
