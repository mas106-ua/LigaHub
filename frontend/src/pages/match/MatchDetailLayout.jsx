import { useEffect, useState } from "react";
import { useParams, Outlet } from "react-router-dom";
import { getMatchDetail } from "../../api/matchdays";
import MatchTabs from "./MatchTabs";
import MatchStatusBadge from "../../components/Matchs/MatchStatusBadge"; // ya lo tienes

export default function MatchDetailLayout() {
  const { id } = useParams();
  const [match, setMatch] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let alive = true;
    setLoading(true);
    getMatchDetail(id)
      .then(data => { if (alive) setMatch(data); })
      .catch(err => { if (alive) setError("Error de red o servidor no responde"); })
      .finally(() => { if (alive) setLoading(false); });
    return () => { alive = false; };
  }, [id]);

  if (loading) return <div className="container py-4">Cargando partido…</div>;
  if (error)   return <div className="container py-4"><div className="alert alert-danger">{error}</div></div>;
  if (!match)  return <div className="container py-4">No encontrado.</div>;

  const dt = match.scheduled_at ? new Date(match.scheduled_at.replace(" ", "T")) : null;

  return (
    <div className="container">
      {/* Hero + tabs (mismo ancho) */}
      <div className="match-header">
        <div className="match-hero card">
          <div className="match-hero__inner">
            <div className="match-hero__team">
              <div className="match-hero__name">{match.home_team.name}</div>
              <div className="match-hero__short text-muted small">{match.home_team.short_name}</div>
            </div>

            <div className="match-hero__score">
              <div className="match-hero__digits">{match.score.home} - {match.score.away}</div>
              <span className={`badge-status ${match.status}`}>
                {match.status === "scheduled" ? "Pendiente" : match.status}
              </span>
              {match.scheduled_at && (
                <div className="text-muted small mt-1">
                  {new Date(match.scheduled_at.replace(" ", "T")).toLocaleDateString()} ·{" "}
                  {new Date(match.scheduled_at.replace(" ", "T")).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}
                </div>
              )}
              {match.venue && (
                <div className="text-muted small">
                  {match.venue.name}{match.venue.city ? ` · ${match.venue.city}` : ""}
                </div>
              )}
            </div>

            <div className="match-hero__team">
              <div className="match-hero__name">{match.away_team.name}</div>
              <div className="match-hero__short text-muted small">{match.away_team.short_name}</div>
            </div>
          </div>
          <MatchTabs base={`/partido/${id}`} centered />
        </div>
      </div>

      {/* Contenido de la pestaña activa (mismo ancho) */}
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
