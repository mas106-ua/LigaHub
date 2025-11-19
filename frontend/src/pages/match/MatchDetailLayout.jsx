import { useEffect, useState, useCallback } from "react";
import { useParams, Outlet } from "react-router-dom";
import { getMatchDetail } from "../../api/matchdays";
import MatchTabs from "./MatchTabs";
import { useAuth } from "../../context/AuthContext";
import { openMatch, closeMatch, verifyMatch } from "../../api/adminMatches";

export default function MatchDetailLayout() {
  const { id } = useParams();
  const { user } = useAuth();
  const [match, setMatch] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [lockLoading, setLockLoading] = useState(false);

  const isAdmin =
    user && user.role && user.role.toLowerCase().includes("admin");
  const isSuperadmin = user && user.role === "superadmin";

  const loadMatch = useCallback(() => {
    let alive = true;
    setLoading(true);
    setError("");

    getMatchDetail(id)
      .then((data) => {
        if (alive) setMatch(data);
      })
      .catch(() => {
        if (alive)
          setError("Error de red o el servidor no responde al cargar el partido.");
      })
      .finally(() => {
        if (alive) setLoading(false);
      });

    return () => {
      alive = false;
    };
  }, [id]);

  useEffect(() => {
    const cleanup = loadMatch();
    return cleanup;
  }, [loadMatch]);

  const handleLockAction = async (action) => {
    if (!match) return;
    setLockLoading(true);
    try {
      if (action === "open") {
        await openMatch(match.id);
      } else if (action === "close") {
        await closeMatch(match.id);
      } else if (action === "verify") {
        await verifyMatch(match.id);
      }
      await loadMatch();
    } catch (err) {
      console.error(err);
      alert(err.message || "Error al actualizar el estado de edición del partido.");
    } finally {
      setLockLoading(false);
    }
  };

  const prettyEditStatus = (s) => {
    if (!s) return "Abierto";
    if (s === "open") return "Abierto";
    if (s === "closed") return "Cerrado";
    if (s === "verified") return "Verificado";
    return s;
  };

  if (loading) {
    return <div className="container py-4">Cargando partido…</div>;
  }
  if (error) {
    return (
      <div className="container py-4">
        <div className="alert alert-danger">{error}</div>
      </div>
    );
  }
  if (!match) {
    return <div className="container py-4">No encontrado.</div>;
  }

  const dt = match.scheduled_at
    ? new Date(match.scheduled_at.replace(" ", "T"))
    : null;

  const editStatus = match.edit_status || "open";

  const canReopen =
    editStatus !== "open" &&
    (editStatus !== "verified" || isSuperadmin);

  const canClose = editStatus === "open";
  const canVerify = editStatus !== "verified" && match.status === "played";

  return (
    <div className="container">
      {/* Cabecera + tabs */}
      <div className="match-header">
        <div className="match-hero card">
          <div className="match-hero__inner">
            {/* Local */}
            <div className="match-hero__team">
              <div className="match-hero__name">{match.home_team.name}</div>
              <div className="match-hero__short text-muted small">
                {match.home_team.short_name}
              </div>
            </div>

            {/* Marcador + estado + acciones */}
            <div className="match-hero__score text-center">
              <div className="match-hero__digits">
                {match.score.home} - {match.score.away}
              </div>

              <span className={`badge-status ${match.status}`}>
                {match.status === "scheduled" ? "Pendiente" : match.status}
              </span>

              {dt && (
                <div className="text-muted small mt-1">
                  {dt.toLocaleDateString()} ·{" "}
                  {dt.toLocaleTimeString([], {
                    hour: "2-digit",
                    minute: "2-digit",
                  })}
                </div>
              )}

              {match.venue && (
                <div className="text-muted small">
                  {match.venue.name} {match.venue.city && `· ${match.venue.city}`}
                </div>
              )}

              {isAdmin && (
                <div className="mt-2">
                  <small className="text-muted d-block mb-1">
                    Estado de edición:{" "}
                    <strong>{prettyEditStatus(editStatus)}</strong>
                  </small>
                  <div className="btn-group btn-group-sm">
                    {canReopen && (
                      <button
                        type="button"
                        className="btn btn-outline-secondary"
                        onClick={() => handleLockAction("open")}
                        disabled={lockLoading}
                      >
                        Reabrir
                      </button>
                    )}

                    {canClose && (
                      <button
                        type="button"
                        className="btn btn-outline-warning"
                        onClick={() => handleLockAction("close")}
                        disabled={lockLoading}
                      >
                        Cerrar edición
                      </button>
                    )}

                    {canVerify && (
                      <button
                        type="button"
                        className="btn btn-success"
                        onClick={() => handleLockAction("verify")}
                        disabled={lockLoading}
                      >
                        Verificar
                      </button>
                    )}
                  </div>
                  {editStatus === "verified" && !isSuperadmin && (
                    <div className="text-muted small mt-1">
                      Solo un superadmin puede reabrir un partido verificado.
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* Visitante */}
            <div className="match-hero__team text-end">
              <div className="match-hero__name">{match.away_team.name}</div>
              <div className="match-hero__short text-muted small">
                {match.away_team.short_name}
              </div>
            </div>
          </div>

          <MatchTabs base={`/partido/${id}`} centered />
        </div>
      </div>

      {/* Contenido de la pestaña activa */}
      <div className="match-content mt-3">
        <Outlet context={{ match }} />
      </div>
    </div>
  );
}

function TeamBlock({ team, score, align="start" }) {
  return (
    <div className={`d-flex flex-column ${align === "end" ? "align-items-end" : "align-items-start"}`}>
      <div className="fw-semibold">{team.name}</div>
      {/* si tienes escudos: <img src={team.crest_url} alt="" style={{height:32}} /> */}
      <div className="text-muted small">{team.short_name}</div>
    </div>
  );
}
