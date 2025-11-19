// src/pages/admin/AdminMatchEventsPage.jsx
import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { getMatchDetail } from "../../api/matchdays";
import { fetchTeamPlayers } from "../../api/teams";
import { updateMatchEvents } from "../../api/adminMatches";

export default function AdminMatchEventsPage() {
  const { matchId } = useParams();

  const [match, setMatch] = useState(null);
  const [playersHome, setPlayersHome] = useState([]);
  const [playersAway, setPlayersAway] = useState([]);
  const [events, setEvents] = useState([]);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  // Usamos 'sub_in' como tipo de cambio en BD, pero lo mostramos como "Cambio"
  const EVENT_TYPES = [
    { value: "goal", label: "Gol" },
    { value: "own_goal", label: "Gol p.p." },
    { value: "yellow", label: "Amarilla" },
    { value: "red", label: "Roja" },
    { value: "sub_in", label: "Cambio" },
  ];

  // ============ LOAD ============

  useEffect(() => {
    let cancel = false;

    (async () => {
      try {
        setLoading(true);
        setError("");
        setSuccess("");

        const m = await getMatchDetail(matchId);
        if (cancel) return;

        setMatch(m);

        const homeId = m?.home_team?.id;
        const awayId = m?.away_team?.id;

        if (homeId) {
          try {
            setPlayersHome(await fetchTeamPlayers(homeId));
          } catch {
            setPlayersHome([]);
          }
        }
        if (awayId) {
          try {
            setPlayersAway(await fetchTeamPlayers(awayId));
          } catch {
            setPlayersAway([]);
          }
        }

        const mapped = (m.events ?? []).map((e) => {
          const teamId = e.team_id ?? e.team?.id ?? null;
          const side =
            teamId === homeId
              ? "home"
              : teamId === awayId
              ? "away"
              : "home";

          return {
            id: e.id,
            side,
            minute: e.minute ?? "",
            type: e.type ?? "",
            player_id: e.player?.id ?? e.player_id ?? "",
            related_player_id:
              e.related_player?.id ?? e.related_player_id ?? "",
            detail: e.detail ?? "",
          };
        });

        setEvents(mapped);
      } catch (e) {
        setError("Error cargando datos del partido.");
      } finally {
        setLoading(false);
      }
    })();

    return () => {
      cancel = true;
    };
  }, [matchId]);

  // ============ HANDLERS ============

  const updateEvent = (idx, patch) => {
    setEvents((prev) =>
      prev.map((ev, i) => (i === idx ? { ...ev, ...patch } : ev))
    );
  };

  const addEvent = () => {
    setEvents((prev) => [
      ...prev,
      {
        id: null,
        side: "home",
        minute: "",
        type: "",
        player_id: "",
        related_player_id: "",
        detail: "",
      },
    ]);
  };

  const removeEvent = (idx) => {
    setEvents((prev) => prev.filter((_, i) => i !== idx));
  };

  const handleSave = async () => {
    try {
      setSaving(true);
      setError("");
      setSuccess("");

      await updateMatchEvents(matchId, events);

      setSuccess("Eventos guardados correctamente.");
    } catch (e) {
      setError(e?.message || "Error guardando eventos.");
    } finally {
      setSaving(false);
    }
  };

  // ============ RENDER ============

  if (loading) return <div className="container py-4">Cargando eventos…</div>;
  if (!match) return <div className="container py-4">Partido no encontrado.</div>;

  return (
    <div className="container py-4">
      <h2 className="h4 mb-3">Eventos del partido</h2>

      {error && <div className="alert alert-danger">{error}</div>}
      {success && <div className="alert alert-success">{success}</div>}

      <button className="btn btn-sm btn-primary mb-3" onClick={addEvent}>
        + Añadir evento
      </button>

      <div className="table-responsive">
        <table className="table table-sm table-bordered align-middle">
          <thead>
            <tr className="table-light">
              <th>Lado</th>
              <th>Min</th>
              <th>Tipo</th>
              <th>Jugador (entra)</th>
              <th>Jugador (sale)</th>
              <th>Detalle</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {events.map((ev, idx) => {
              const players =
                ev.side === "home" ? playersHome : playersAway;
              const isSub = ev.type === "sub_in";

              return (
                <tr key={idx}>
                  <td>
                    <select
                      className="form-select form-select-sm"
                      value={ev.side}
                      onChange={(e) =>
                        updateEvent(idx, { side: e.target.value })
                      }
                    >
                      <option value="home">Local</option>
                      <option value="away">Visitante</option>
                    </select>
                  </td>

                  <td width="80">
                    <input
                      type="number"
                      min="0"
                      className="form-control form-control-sm"
                      value={ev.minute}
                      onChange={(e) =>
                        updateEvent(idx, { minute: e.target.value })
                      }
                    />
                  </td>

                  <td>
                    <select
                      className="form-select form-select-sm"
                      value={ev.type}
                      onChange={(e) => {
                        const newType = e.target.value;
                        updateEvent(idx, {
                          type: newType,
                          // si deja de ser cambio, limpiamos el que sale
                          related_player_id:
                            newType === "sub_in" ? ev.related_player_id : "",
                        });
                      }}
                    >
                      <option value="">—</option>
                      {EVENT_TYPES.map((t) => (
                        <option key={t.value} value={t.value}>
                          {t.label}
                        </option>
                      ))}
                    </select>
                  </td>

                  <td>
                    <select
                      className="form-select form-select-sm"
                      value={ev.player_id}
                      onChange={(e) =>
                        updateEvent(idx, { player_id: e.target.value })
                      }
                    >
                      <option value="">—</option>
                      {players.map((p) => (
                        <option key={p.id} value={p.id}>
                          {p.name}
                        </option>
                      ))}
                    </select>
                  </td>

                  <td>
                    <select
                      className="form-select form-select-sm"
                      value={ev.related_player_id}
                      onChange={(e) =>
                        updateEvent(idx, {
                          related_player_id: e.target.value,
                        })
                      }
                      disabled={!isSub}
                    >
                      <option value="">—</option>
                      {players.map((p) => (
                        <option key={p.id} value={p.id}>
                          {p.name}
                        </option>
                      ))}
                    </select>
                  </td>

                  <td>
                    <input
                      className="form-control form-control-sm"
                      value={ev.detail}
                      onChange={(e) =>
                        updateEvent(idx, { detail: e.target.value })
                      }
                    />
                  </td>

                  <td width="50">
                    <button
                      className="btn btn-sm btn-danger"
                      onClick={() => removeEvent(idx)}
                    >
                      X
                    </button>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <button
        className="btn btn-primary mt-3"
        disabled={saving}
        onClick={handleSave}
      >
        {saving ? "Guardando…" : "Guardar cambios"}
      </button>
    </div>
  );
}
