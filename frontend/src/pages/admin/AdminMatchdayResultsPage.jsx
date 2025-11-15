// src/pages/admin/AdminMatchdayResultsPage.jsx
import { useEffect, useMemo, useState } from "react";
import { useParams } from "react-router-dom";
import {
  fetchMatchesByMatchday,
  fetchLeagueSummary,
} from "../../api/matchdays";
import { updateMatchdayResults } from "../../api/adminMatches";

const STATUS_OPTIONS = [
  { value: "scheduled", label: "Programado" },
  { value: "played", label: "Finalizado" },
  { value: "postponed", label: "Aplazado" },
  { value: "canceled", label: "Cancelado" },
];

const fmtDate = (iso) => {
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

export default function AdminMatchdayResultsPage() {
  const { leagueId, matchdayNumber } = useParams();
  const mdNumber = Number(matchdayNumber);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [league, setLeague] = useState(null);
  const [rows, setRows] = useState([]);

  const [reloadKey, setReloadKey] = useState(0);

  // Cargar liga + partidos de la jornada
  useEffect(() => {
    let cancel = false;

    (async () => {
      try {
        setLoading(true);
        setError("");
        setSuccess("");

        const [summary, matches] = await Promise.all([
          fetchLeagueSummary(leagueId),
          // sin filtros extra: todos los partidos de la jornada
          fetchMatchesByMatchday(leagueId, mdNumber),
        ]);

        if (cancel) return;

        setLeague(summary);

        const mapped = matches.map((m) => ({
          id: m.id,
          scheduled_at: m.scheduled_at,
          venue: m.venue,
          home_team_name:
            m.home_team?.name ?? (m.home_team ? `Equipo ${m.home_team.id}` : "—"),
          away_team_name:
            m.away_team?.name ?? (m.away_team ? `Equipo ${m.away_team.id}` : "—"),
          status: m.status || "scheduled",
          home_goals:
            m.score && typeof m.score.home === "number"
              ? String(m.score.home)
              : "",
          away_goals:
            m.score && typeof m.score.away === "number"
              ? String(m.score.away)
              : "",
        }));

        setRows(mapped);
      } catch (e) {
        if (!cancel) {
          setError(e?.message || "Error cargando partidos de la jornada");
        }
      } finally {
        if (!cancel) setLoading(false);
      }
    })();

    return () => {
      cancel = true;
    };
  }, [leagueId, mdNumber, reloadKey]);

  const updateRow = (id, patch) => {
    setRows((prev) => prev.map((r) => (r.id === id ? { ...r, ...patch } : r)));
  };

  const hasPlayedMissingGoals = useMemo(
    () =>
      rows.some(
        (r) =>
          r.status === "played" &&
          (r.home_goals === "" || r.away_goals === "")
      ),
    [rows]
  );

  const handleSave = async () => {
    try {
      setSaving(true);
      setError("");
      setSuccess("");

      const payloadRows = rows.map((r) => ({
        id: r.id,
        home_goals:
          r.home_goals === "" || r.home_goals === null
            ? null
            : Number(r.home_goals),
        away_goals:
          r.away_goals === "" || r.away_goals === null
            ? null
            : Number(r.away_goals),
        status: r.status,
      }));

      await updateMatchdayResults(leagueId, mdNumber, payloadRows);

      setSuccess("Resultados guardados correctamente.");
      // si quieres reflejar lo que devuelva BE, podrías recargar:
      // setReloadKey((k) => k + 1);
    } catch (e) {
      setError(e?.message || "Error guardando resultados");
    } finally {
      setSaving(false);
    }
  };

  const handleReload = () => {
    setReloadKey((k) => k + 1);
  };

  return (
    <div className="container py-4">
      <div className="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h1 className="h4 mb-1">Edición de resultados — Jornada {mdNumber}</h1>
          {league && (
            <div className="text-muted small">
              {league.name}
              {league.season ? ` · ${league.season}` : ""}
              {league.level ? ` · ${league.level}` : ""}
            </div>
          )}
        </div>

        <div className="d-flex align-items-center gap-2">
          <button
            type="button"
            className="btn btn-outline-secondary btn-sm"
            onClick={handleReload}
            disabled={loading || saving}
          >
            Recargar
          </button>
          <button
            type="button"
            className="btn btn-primary btn-sm"
            onClick={handleSave}
            disabled={saving || loading || !rows.length || hasPlayedMissingGoals}
          >
            {saving ? "Guardando..." : "Guardar cambios"}
          </button>
        </div>
      </div>

      {hasPlayedMissingGoals && (
        <div className="alert alert-warning py-2">
          Hay partidos marcados como <strong>Finalizado</strong> sin ambos goles
          informados.
        </div>
      )}

      {error && (
        <div className="alert alert-danger py-2">
          {error}
        </div>
      )}

      {success && (
        <div className="alert alert-success py-2">
          {success}
        </div>
      )}

      {loading && (
        <div className="text-muted">Cargando partidos de la jornada...</div>
      )}

      {!loading && !rows.length && !error && (
        <div className="text-muted">No hay partidos en esta jornada.</div>
      )}

      {!loading && rows.length > 0 && (
        <div className="table-responsive">
          <table className="table align-middle">
            <thead>
              <tr>
                <th style={{ width: "22%" }}>Fecha / sede</th>
                <th style={{ width: "28%" }}>Local</th>
                <th style={{ width: "28%" }}>Visitante</th>
                <th style={{ width: "12%" }} className="text-center">
                  Goles
                </th>
                <th style={{ width: "10%" }}>Estado</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id}>
                  <td>
                    <div className="small text-muted">
                      {fmtDate(r.scheduled_at)}
                      {r.venue ? ` · ${r.venue}` : ""}
                    </div>
                  </td>
                  <td>
                    <div className="fw-semibold">{r.home_team_name}</div>
                  </td>
                  <td>
                    <div className="fw-semibold">{r.away_team_name}</div>
                  </td>
                  <td>
                    <div className="d-flex align-items-center justify-content-center gap-1">
                      <input
                        type="number"
                        min="0"
                        max="20"
                        className="form-control form-control-sm text-center"
                        style={{ maxWidth: "60px" }}
                        value={r.home_goals}
                        onChange={(e) =>
                          updateRow(r.id, {
                            home_goals:
                              e.target.value === ""
                                ? ""
                                : e.target.value.replace(/\D/g, ""),
                          })
                        }
                      />
                      <span className="mx-1">-</span>
                      <input
                        type="number"
                        min="0"
                        max="20"
                        className="form-control form-control-sm text-center"
                        style={{ maxWidth: "60px" }}
                        value={r.away_goals}
                        onChange={(e) =>
                          updateRow(r.id, {
                            away_goals:
                              e.target.value === ""
                                ? ""
                                : e.target.value.replace(/\D/g, ""),
                          })
                        }
                      />
                    </div>
                  </td>
                  <td>
                    <select
                      className="form-select form-select-sm"
                      value={r.status}
                      onChange={(e) =>
                        updateRow(r.id, { status: e.target.value })
                      }
                    >
                      {STATUS_OPTIONS.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                          {opt.label}
                        </option>
                      ))}
                    </select>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
