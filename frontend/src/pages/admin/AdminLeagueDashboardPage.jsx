import { useEffect, useMemo, useState } from "react";
import { Link, useNavigate, useParams, useSearchParams } from "react-router-dom";
import {
  fetchLeagueSummary,
  fetchMatchdays,
  fetchMatchesByMatchday,
} from "../../api/matchdays";

function fmtDateTime(dt) {
  if (!dt) return "";
  try {
    const d = new Date(dt);
    return d.toLocaleString("es-ES", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return String(dt);
  }
}

function badgeForStatus(status) {
  const s = String(status || "").toLowerCase();
  if (s === "played") return <span className="badge text-bg-success">Jugado</span>;
  if (s === "scheduled") return <span className="badge text-bg-secondary">Programado</span>;
  if (s === "postponed") return <span className="badge text-bg-warning">Aplazado</span>;
  return <span className="badge text-bg-light text-dark">{status || "—"}</span>;
}

export default function AdminLeagueDashboardPage() {
  const { leagueId } = useParams();
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();

  const [league, setLeague] = useState(null);

  const [matchdays, setMatchdays] = useState([]);
  const [selectedMatchday, setSelectedMatchday] = useState(null);

  const [matches, setMatches] = useState([]);

  const [loading, setLoading] = useState(true);
  const [loadingMatches, setLoadingMatches] = useState(false);
  const [error, setError] = useState("");

  const selectedFromUrl = useMemo(() => {
    const raw = searchParams.get("md");
    const n = raw ? parseInt(raw, 10) : null;
    return Number.isFinite(n) ? n : null;
  }, [searchParams]);

  // Carga datos base: resumen liga + jornadas
  useEffect(() => {
    let mounted = true;
    setLoading(true);
    setError("");

    Promise.all([fetchLeagueSummary(leagueId), fetchMatchdays(leagueId)])
      .then(([lg, mds]) => {
        if (!mounted) return;

        setLeague(lg || null);

        const list = Array.isArray(mds) ? mds : [];
        setMatchdays(list);

        // Selección inicial: md de URL -> si existe en lista -> si no, primera jornada
        const fallback = list?.[0]?.number ?? lg?.min_matchday ?? 1;
        const initial =
          selectedFromUrl && list.some((x) => x.number === selectedFromUrl)
            ? selectedFromUrl
            : fallback;

        setSelectedMatchday(initial);
      })
      .catch(() => {
        if (!mounted) return;
        setError("No se pudo cargar el panel de administración de la liga.");
      })
      .finally(() => mounted && setLoading(false));

    return () => {
      mounted = false;
    };
  }, [leagueId, selectedFromUrl]);

  // Carga partidos de la jornada seleccionada
  useEffect(() => {
    if (!selectedMatchday) return;

    let mounted = true;
    setLoadingMatches(true);

    fetchMatchesByMatchday(leagueId, selectedMatchday)
      .then((rows) => {
        if (!mounted) return;
        setMatches(Array.isArray(rows) ? rows : []);
      })
      .catch(() => {
        if (!mounted) return;
        setMatches([]);
      })
      .finally(() => mounted && setLoadingMatches(false));

    return () => {
      mounted = false;
    };
  }, [leagueId, selectedMatchday]);

  const onPickMatchday = (n) => {
    setSelectedMatchday(n);
    const next = new URLSearchParams(searchParams);
    next.set("md", String(n));
    setSearchParams(next, { replace: true });
  };

  const goToResults = () => {
    if (!selectedMatchday) return;
    navigate(`/admin/ligas/${leagueId}/jornadas/${selectedMatchday}/resultados`);
  };

  const title = league?.name || `Liga #${leagueId}`;

  if (loading) {
    return (
      <div className="container py-4">
        <div className="placeholder-glow">
          <div className="placeholder col-6 mb-2" style={{ height: 28 }} />
          <div className="placeholder col-12" style={{ height: 140 }} />
        </div>
      </div>
    );
  }

  return (
    <div className="container py-4">
      <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
          <h2 className="mb-0">Panel de liga</h2>
          <div className="text-muted">
            <strong>{title}</strong>
            {league?.season ? <span> · {league.season}</span> : null}
          </div>

          <div className="mt-2 d-flex flex-wrap gap-2">
            <Link className="btn btn-sm btn-outline-secondary" to={`/comp/${leagueId}`}>
              Ver público
            </Link>
          </div>
        </div>

        <div className="d-flex flex-wrap gap-2">
          <button
            className="btn btn-primary"
            onClick={goToResults}
            disabled={!selectedMatchday}
            title="Editar resultados de la jornada seleccionada"
          >
            Editar resultados (J{selectedMatchday ?? "—"})
          </button>
        </div>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {/* Jornada selector */}
      <div className="card border-0 shadow-sm mb-3">
        <div className="card-body">
          <div className="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div className="fw-semibold">Selecciona jornada</div>
            <div className="text-muted small">
              Tip: se guarda en la URL con <code>?md=</code>
            </div>
          </div>

          {matchdays.length === 0 ? (
            <div className="alert alert-secondary mt-3 mb-0">
              No hay jornadas disponibles todavía (¿hay partidos creados?).
            </div>
          ) : (
            <div className="d-flex flex-wrap gap-2 mt-3">
              {matchdays.map((md) => {
                const isActive = md.number === selectedMatchday;
                const total = md.matches_count ?? 0;
                const played = md.played_count ?? 0;

                return (
                  <button
                    key={md.number}
                    type="button"
                    className={`btn btn-sm ${isActive ? "btn-primary" : "btn-outline-primary"}`}
                    onClick={() => onPickMatchday(md.number)}
                    title={
                      md.first_date
                        ? `(${md.first_date} – ${md.last_date})`
                        : ""
                    }
                  >
                    J{md.number}{" "}
                    <span className={`ms-1 badge ${isActive ? "text-bg-light text-dark" : "text-bg-primary"}`}>
                      {played}/{total}
                    </span>
                  </button>
                );
              })}
            </div>
          )}
        </div>
      </div>

      {/* Accesos directos a partidos de la jornada */}
      <div className="card border-0 shadow-sm">
        <div className="card-body">
          <div className="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <div className="fw-semibold">Partidos de la jornada {selectedMatchday ?? "—"}</div>
              <div className="text-muted small">
                Accede a <strong>Alineaciones</strong> y <strong>Eventos</strong> sin tener que escribir la URL.
              </div>
            </div>

            <button
              className="btn btn-sm btn-outline-secondary"
              onClick={() => selectedMatchday && onPickMatchday(selectedMatchday)}
              disabled={!selectedMatchday || loadingMatches}
              title="Recargar partidos de esta jornada"
            >
              {loadingMatches ? "Cargando..." : "Recargar"}
            </button>
          </div>

          {loadingMatches ? (
            <div className="mt-3">
              <div className="placeholder-glow">
                <div className="placeholder col-12 mb-2" style={{ height: 52 }} />
                <div className="placeholder col-12 mb-2" style={{ height: 52 }} />
                <div className="placeholder col-12" style={{ height: 52 }} />
              </div>
            </div>
          ) : matches.length === 0 ? (
            <div className="alert alert-secondary mt-3 mb-0">
              No hay partidos para esta jornada.
            </div>
          ) : (
            <div className="table-responsive mt-3">
              <table className="table align-middle">
                <thead>
                  <tr>
                    <th>Partido</th>
                    <th style={{ width: 180 }}>Fecha</th>
                    <th style={{ width: 120 }}>Estado</th>
                    <th style={{ width: 320 }}>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {matches.map((m) => (
                    <tr key={m.id}>
                      <td>
                        <div className="fw-semibold">
                          {m.home_team?.name} <span className="text-muted">vs</span>{" "}
                          {m.away_team?.name}
                        </div>
                        <div className="small text-muted">
                          Marcador:{" "}
                          <strong>
                            {(m.score?.home ?? "—")} - {(m.score?.away ?? "—")}
                          </strong>
                        </div>
                      </td>
                      <td className="text-muted">{fmtDateTime(m.scheduled_at)}</td>
                      <td>{badgeForStatus(m.status)}</td>
                      <td>
                        <div className="d-flex flex-wrap gap-2">
                          <Link className="btn btn-sm btn-outline-secondary" to={`/partido/${m.id}`}>
                            Abrir
                          </Link>
                          <Link
                            className="btn btn-sm btn-outline-primary"
                            to={`/admin/partidos/${m.id}/alineaciones`}
                          >
                            Alineaciones
                          </Link>
                          <Link
                            className="btn btn-sm btn-outline-primary"
                            to={`/admin/partidos/${m.id}/eventos`}
                          >
                            Eventos
                          </Link>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>

              <div className="text-muted small">
                Nota: los botones de administración requieren estar logueado como <code>admin</code> o <code>superadmin</code>.
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
