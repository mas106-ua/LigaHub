// src/pages/admin/AdminMatchLineupsPage.jsx
import { useEffect, useMemo, useState } from "react";
import { useParams, Link } from "react-router-dom";
import { getMatchDetail } from "../../api/matchdays";
import { fetchTeamPlayers } from "../../api/teams";
import { updateMatchLineups } from "../../api/adminMatches";
import { useAuth } from "../../context/AuthContext";

const FORMATIONS = ["4-3-3", "4-4-2", "3-5-2", "5-3-2", "4-2-3-1", "4-1-4-1"];

/* ================= helpers de posiciones / formación ================= */

function normalizePos(raw) {
  const t = (raw || "").toUpperCase();
  if (t.includes("GK") || t.includes("POR")) return "GK";
  if (/(RB|LB|RWB|LWB|CB|DF)/.test(t)) return "DF";
  if (/(LW|RW|FW|ST|CF|DL)/.test(t)) return "FW";
  return "MF";
}

function parseFormation(formation) {
  if (!formation) return { df: 4, mf: 3, fw: 3 };
  const numbers = formation
    .split("-")
    .map((n) => parseInt(n, 10))
    .filter((n) => !isNaN(n));

  if (numbers.length < 2) return { df: 4, mf: 3, fw: 3 };

  const df = numbers[0];
  const fw = numbers[numbers.length - 1];
  const mf = numbers.slice(1, -1).reduce((sum, n) => sum + n, 0);

  if (df + mf + fw !== 10) {
    const rest = 10 - df - fw;
    return {
      df: df > 0 ? df : 4,
      mf: rest > 0 ? rest : 3,
      fw: fw > 0 ? fw : 3,
    };
  }

  return { df, mf, fw };
}

function buildSlots(formation) {
  const { df, mf, fw } = parseFormation(formation);
  const slots = ["GK"];
  for (let i = 0; i < df; i++) slots.push("DF");
  for (let i = 0; i < mf; i++) slots.push("MF");
  for (let i = 0; i < fw; i++) slots.push("FW");
  while (slots.length < 11) slots.push("MF");
  return slots.slice(0, 11);
}

/* ===================== inicialización de estado ===================== */

function buildStartersFromExisting(lineup, formation) {
  const slots = buildSlots(formation);
  const starters = Array.isArray(lineup?.starters) ? lineup.starters : [];
  const byPos = { GK: [], DF: [], MF: [], FW: [] };
  const others = [];

  starters.forEach((s) => {
    const pos = normalizePos(s.pos);
    if (byPos[pos]) byPos[pos].push(s);
    else others.push(s);
  });

  const used = new Set();
  return slots.map((slot) => {
    let chosen =
      byPos[slot]?.find((s) => !used.has(s.player_id)) ||
      others.find((s) => !used.has(s.player_id));

    if (chosen) {
      used.add(chosen.player_id);
      return {
        slot,
        player_id: chosen.player_id ?? null, // número o null
      };
    }

    return { slot, player_id: null };
  });
}

function initSideState(lineup) {
  const formation = lineup?.formation || "4-3-3";
  const coach_name = lineup?.coach_name || "";
  const starters = buildStartersFromExisting(lineup, formation);
  const bench = Array.isArray(lineup?.bench)
    ? lineup.bench.map((b) => ({
        player_id: b.player_id ?? null,
      }))
    : [];

  return { formation, coach_name, starters, bench };
}

/* ======================== payload + validaciones ======================== */

function sideToPayload(state, players) {
  if (!state) return undefined;

  const starters = state.starters.map((row) => {
    const player = players.find((p) => p.id === row.player_id) || null;
    const pos =
      row.slot || normalizePos(player ? player.position : undefined);
    const shirt =
      player && player.shirt_number != null
        ? Number(player.shirt_number)
        : null;

    return {
      player_id: row.player_id,
      shirt,
      pos,
    };
  });

  const bench = (state.bench || [])
    .filter((b) => b.player_id)
    .map((b) => {
      const player = players.find((p) => p.id === b.player_id) || null;
      const shirt =
        player && player.shirt_number != null
          ? Number(player.shirt_number)
          : null;
      return {
        player_id: b.player_id,
        shirt,
        pos: normalizePos(player ? player.position : undefined),
      };
    });

  return {
    formation: state.formation || null,
    coach_name: state.coach_name || null,
    starters,
    bench,
  };
}

function validateSide(state) {
  if (!state) return [];
  const starters = state.starters || [];
  const filled = starters.filter((r) => r.player_id).length;
  const gkSlot = starters.find((r) => r.slot === "GK");
  const gkFilled = gkSlot && gkSlot.player_id ? 1 : 0;

  const errors = [];
  if (filled !== 11) {
    errors.push(`Debe haber exactamente 11 titulares (actualmente ${filled}).`);
  }
  if (gkFilled !== 1) {
    errors.push(
      `Debe haber exactamente 1 portero (GK) en el once titular (actualmente ${gkFilled}).`
    );
  }

  const allIds = [
    ...starters.map((r) => r.player_id).filter(Boolean),
    ...(state.bench || []).map((b) => b.player_id).filter(Boolean),
  ];
  const uniq = new Set(allIds);
  if (uniq.size !== allIds.length) {
    errors.push("Hay jugadores duplicados entre titulares y banquillo.");
  }

  return errors;
}

/* =========================== SideEditor =========================== */

function SideEditor({ label, team, players, state, onChange }) {
  if (!team) {
    return (
      <div className="col-md-6 mb-3">
        <div className="card h-100">
          <div className="card-header">{label}</div>
          <div className="card-body">
            <p className="text-muted mb-0">
              No se ha encontrado el equipo para este lado.
            </p>
          </div>
        </div>
      </div>
    );
  }

  const starters = state?.starters || [];
  const bench = state?.bench || [];

  const handleFormationChange = (value) => {
    const slots = buildSlots(value);
    const prev = starters;
    const rows = slots.map((slot, idx) => {
      const prevRow = prev[idx];
      if (!prevRow) return { slot, player_id: null };
      return { ...prevRow, slot };
    });
    onChange({ ...state, formation: value, starters: rows });
  };

  const handleStarterChange = (index, field, value) => {
    const next = starters.map((row, i) =>
      i === index ? { ...row, [field]: value } : row
    );
    onChange({ ...state, starters: next });
  };

  const handleBenchChange = (index, field, value) => {
    const next = bench.map((row, i) =>
      i === index ? { ...row, [field]: value } : row
    );
    onChange({ ...state, bench: next });
  };

  const addBenchRow = () => {
    onChange({
      ...state,
      bench: [...bench, { player_id: null }],
    });
  };

  const removeBenchRow = (idx) => {
    onChange({
      ...state,
      bench: bench.filter((_, i) => i !== idx),
    });
  };

  // Jugadores agrupados por posición (GK/DF/MF/FW)
  const playersByPos = useMemo(() => {
    const map = { GK: [], DF: [], MF: [], FW: [] };
    players.forEach((p) => {
      const pos = normalizePos(p.position);
      if (map[pos]) map[pos].push(p);
    });
    return map;
  }, [players]);

  // Conjunto de IDs ya usados en titulares + banquillo
  const usedIds = new Set([
    ...starters.map((r) => r.player_id).filter(Boolean),
    ...bench.map((r) => r.player_id).filter(Boolean),
  ]);

  const startersGk = starters.filter(
    (r) => r.slot === "GK" && r.player_id
  ).length;
  const startersFilled = starters.filter((r) => r.player_id).length;

  return (
    <div className="col-md-6 mb-3">
      <div className="card h-100">
        <div className="card-header">
          {label} — {team.name}
        </div>
        <div className="card-body">
          {/* formación + entrenador */}
          <div className="row mb-3">
            <div className="col-sm-6 mb-2 mb-sm-0">
              <label className="form-label form-label-sm">Formación</label>
              <select
                className="form-select form-select-sm"
                value={state?.formation || ""}
                onChange={(e) => handleFormationChange(e.target.value)}
              >
                <option value="">Seleccionar formación</option>
                {FORMATIONS.map((f) => (
                  <option key={f} value={f}>
                    {f}
                  </option>
                ))}
              </select>
            </div>
            <div className="col-sm-6">
              <label className="form-label form-label-sm">Entrenador</label>
              <input
                type="text"
                className="form-control form-control-sm"
                value={state?.coach_name || ""}
                onChange={(e) =>
                  onChange({ ...state, coach_name: e.target.value })
                }
                placeholder="Nombre del entrenador"
              />
            </div>
          </div>

          {/* titulares */}
          <div className="mb-2 d-flex justify-content-between align-items-center">
            <div className="fw-semibold">Titulares (11)</div>
            <div className="small text-muted">
              GK: {startersGk} · Jugadores: {startersFilled}/11
            </div>
          </div>

          <div className="table-responsive mb-3">
            <table className="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th style={{ width: "5%" }}>#</th>
                  <th style={{ width: "55%" }}>Jugador</th>
                  <th style={{ width: "20%" }}>Pos.</th>
                  <th style={{ width: "20%" }}>Dorsal</th>
                </tr>
              </thead>
              <tbody>
                {starters.map((row, idx) => {
                  const baseOptions = playersByPos[row.slot] || [];
                  // opciones = jugadores de esa línea que NO estén usados,
                  // salvo el propio jugador ya seleccionado en esta fila
                  const options = baseOptions.filter(
                    (p) => !usedIds.has(p.id) || p.id === row.player_id
                  );
                  const player =
                    baseOptions.find((p) => p.id === row.player_id) || null;
                  const shirt = player?.shirt_number ?? "";

                  return (
                    <tr key={idx}>
                      <td className="small">{idx + 1}</td>
                      <td>
                        <select
                          className="form-select form-select-sm"
                          value={row.player_id ?? ""}
                          onChange={(e) => {
                            const value = e.target.value
                              ? Number(e.target.value)
                              : null;
                            handleStarterChange(idx, "player_id", value);
                          }}
                        >
                          <option value="">
                            Seleccionar{" "}
                            {row.slot === "GK"
                              ? "portero"
                              : row.slot === "DF"
                              ? "defensa"
                              : row.slot === "MF"
                              ? "centrocampista"
                              : "delantero"}
                            ...
                          </option>
                          {options.map((p) => (
                            <option key={p.id} value={p.id}>
                              {p.name}
                              {p.shirt_number
                                ? ` (#${p.shirt_number})`
                                : ""}
                            </option>
                          ))}
                        </select>
                      </td>
                      <td className="small">
                        {row.slot === "GK"
                          ? "GK"
                          : row.slot === "DF"
                          ? "DF"
                          : row.slot === "MF"
                          ? "MF"
                          : "FW"}
                      </td>
                      <td>
                        <input
                          type="text"
                          className="form-control form-control-sm"
                          value={shirt}
                          readOnly
                          disabled
                        />
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* banquillo */}
          <div className="d-flex align-items-center mb-2">
            <div className="fw-semibold me-2">Banquillo</div>
            <button
              type="button"
              className="btn btn-outline-secondary btn-sm"
              onClick={addBenchRow}
            >
              Añadir suplente
            </button>
          </div>

          <div className="table-responsive">
            <table className="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th style={{ width: "5%" }}></th>
                  <th style={{ width: "55%" }}>Jugador</th>
                  <th style={{ width: "20%" }}>Pos.</th>
                  <th style={{ width: "20%" }}>Dorsal</th>
                </tr>
              </thead>
              <tbody>
                {bench.length === 0 && (
                  <tr>
                    <td colSpan={4} className="small text-muted">
                      Sin suplentes.
                    </td>
                  </tr>
                )}
                {bench.map((row, idx) => {
                  const baseOptions = players;
                  const options = baseOptions.filter(
                    (p) => !usedIds.has(p.id) || p.id === row.player_id
                  );
                  const player =
                    baseOptions.find((p) => p.id === row.player_id) || null;
                  const pos = player ? normalizePos(player.position) : null;
                  const shirt = player?.shirt_number ?? "";

                  return (
                    <tr key={idx}>
                      <td>
                        <button
                          type="button"
                          className="btn btn-link btn-sm text-danger p-0"
                          onClick={() => removeBenchRow(idx)}
                        >
                          ✕
                        </button>
                      </td>
                      <td>
                        <select
                          className="form-select form-select-sm"
                          value={row.player_id ?? ""}
                          onChange={(e) => {
                            const value = e.target.value
                              ? Number(e.target.value)
                              : null;
                            handleBenchChange(idx, "player_id", value);
                          }}
                        >
                          <option value="">Seleccionar jugador...</option>
                          {options.map((p) => (
                            <option key={p.id} value={p.id}>
                              {p.name}
                              {p.shirt_number
                                ? ` (#${p.shirt_number})`
                                : ""}
                            </option>
                          ))}
                        </select>
                      </td>
                      <td className="small">{pos || "-"}</td>
                      <td>
                        <input
                          type="text"
                          className="form-control form-control-sm"
                          value={shirt}
                          readOnly
                          disabled
                        />
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}


/* ======================== página principal ======================== */

export default function AdminMatchLineupsPage() {
  const { matchId } = useParams();
  const { user, isAuth } = useAuth();

  const [loading, setLoading] = useState(true);
  const [match, setMatch] = useState(null);
  const [homePlayers, setHomePlayers] = useState([]);
  const [awayPlayers, setAwayPlayers] = useState([]);
  const [homeState, setHomeState] = useState(null);
  const [awayState, setAwayState] = useState(null);
  const [saving, setSaving] = useState(false);
  const [feedback, setFeedback] = useState({ error: "", success: "" });

  useEffect(() => {
    let cancelled = false;

    (async () => {
      try {
        setLoading(true);
        const m = await getMatchDetail(matchId);
        if (cancelled) return;

        setMatch(m);

        const homeId = m.home_team?.id ?? m.home_team_id;
        const awayId = m.away_team?.id ?? m.away_team_id;

        const [homeP, awayP] = await Promise.all([
          homeId ? fetchTeamPlayers(homeId) : Promise.resolve([]),
          awayId ? fetchTeamPlayers(awayId) : Promise.resolve([]),
        ]);

        if (cancelled) return;

        setHomePlayers(homeP);
        setAwayPlayers(awayP);

        const lineups = Array.isArray(m.lineups) ? m.lineups : [];
        const homeLu = lineups.find((l) => l.side === "home") || {};
        const awayLu = lineups.find((l) => l.side === "away") || {};

        setHomeState(initSideState(homeLu));
        setAwayState(initSideState(awayLu));
      } catch (e) {
        if (!cancelled) {
          setFeedback({
            error: e?.message || "Error cargando datos del partido",
            success: "",
          });
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [matchId]);

  const homeErrors = useMemo(
    () => validateSide(homeState),
    [homeState]
  );
  const awayErrors = useMemo(
    () => validateSide(awayState),
    [awayState]
  );
  const hasErrors = homeErrors.length > 0 || awayErrors.length > 0;

  const handleSave = async () => {
    try {
      setSaving(true);
      setFeedback({ error: "", success: "" });

      const payload = {};
      if (homeState)
        payload.home = sideToPayload(homeState, homePlayers);
      if (awayState)
        payload.away = sideToPayload(awayState, awayPlayers);

      await updateMatchLineups(match.id, payload);

      const fresh = await getMatchDetail(match.id);
      setMatch(fresh);

      setFeedback({
        error: "",
        success: "Alineaciones guardadas correctamente.",
      });
    } catch (e) {
      setFeedback({
        error: e?.message || "Error guardando alineaciones",
        success: "",
      });
    } finally {
      setSaving(false);
    }
  };

  if (!isAuth || user?.role === "user") {
    return <p>No tienes permisos para editar alineaciones.</p>;
  }

  if (loading) {
    return <p className="text-muted">Cargando datos de alineaciones...</p>;
  }

  if (!match) {
    return <p className="text-muted">Partido no encontrado.</p>;
  }

  const homeName =
    match.home_team?.short_name || match.home_team?.name || "Local";
  const awayName =
    match.away_team?.short_name || match.away_team?.name || "Visitante";

  return (
    <div className="container py-3">
      <div className="d-flex justify-content-between align-items-center mb-3">
        <h1 className="h5 mb-0">Alineaciones — partido #{match.id}</h1>
        <Link
          to={`/partido/${match.id}/alineaciones`}
          className="btn btn-outline-secondary btn-sm"
        >
          Volver al partido
        </Link>
      </div>

      <p className="text-muted mb-2">
        {homeName} vs {awayName}
      </p>

      {feedback.error && (
        <div className="alert alert-danger py-1 mb-2">{feedback.error}</div>
      )}
      {feedback.success && (
        <div className="alert alert-success py-1 mb-2">
          {feedback.success}
        </div>
      )}
      {homeErrors.map((msg, i) => (
        <div key={`h-${i}`} className="alert alert-warning py-1 mb-1">
          Local: {msg}
        </div>
      ))}
      {awayErrors.map((msg, i) => (
        <div key={`a-${i}`} className="alert alert-warning py-1 mb-1">
          Visitante: {msg}
        </div>
      ))}

      <div className="d-flex justify-content-end mb-3">
        <button
          type="button"
          className="btn btn-primary btn-sm"
          onClick={handleSave}
          disabled={saving || hasErrors}
        >
          {saving ? "Guardando..." : "Guardar alineaciones"}
        </button>
      </div>

      <div className="row">
        <SideEditor
          label="Equipo local"
          team={match.home_team}
          players={homePlayers}
          state={homeState}
          onChange={setHomeState}
        />
        <SideEditor
          label="Equipo visitante"
          team={match.away_team}
          players={awayPlayers}
          state={awayState}
          onChange={setAwayState}
        />
      </div>
    </div>
  );
}
