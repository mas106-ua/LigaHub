import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import api from "../api/api";
import LeagueHeader from "../components/league/LeagueHeader";
import LeagueSeasonSwitcher from "../components/league/LeagueSeasonSwitcher";

export default function LeagueTeamsPage() {
  const { leagueId } = useParams();

  const [detail, setDetail] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    setLoading(true);
    setError("");

    api
      .get(`/api/leagues/${leagueId}/detail`)
      .then((r) => {
        setDetail(r.data?.data || null);
      })
      .catch(() => {
        setError("No se ha podido cargar la información de la liga.");
        setDetail(null);
      })
      .finally(() => setLoading(false));
  }, [leagueId]);

  const teams = detail?.teams || [];

  return (
    <div className="container">
      {detail && (
        <>
          <LeagueHeader detail={detail} active="equipos" />
          <LeagueSeasonSwitcher />
        </>
      )}

      <h1 className="h4 mb-3">Equipos</h1>

      {loading && (
        <div className="d-flex align-items-center gap-2">
          <div className="spinner-border spinner-border-sm" role="status" />
          <span>Cargando…</span>
        </div>
      )}

      {error && !loading && (
        <div className="alert alert-danger" role="alert">
          {error}
        </div>
      )}

      {!loading && !error && teams.length === 0 && (
        <div className="text-muted">
          No hay equipos registrados para esta liga.
        </div>
      )}

      {!loading && !error && teams.length > 0 && (
        <div className="card">
          <ul className="list-group list-group-flush">
            {teams.map((t) => (
              <li
                key={t.id}
                className="list-group-item d-flex justify-content-between align-items-center"
              >
                <div>
                  <div>{t.name}</div>
                  {t.short_name && (
                    <div className="text-muted small">{t.short_name}</div>
                  )}
                </div>
                {/* Si quieres, aquí más info (grupo, escudo, etc.) */}
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
