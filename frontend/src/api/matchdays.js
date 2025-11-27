// src/api/matchdays.js
/**
 * Módulo de llamadas públicas para FE-03 (jornadas + partidos)
 * Endpoints usados:
 *  - GET /leagues/:id/matchdays              (?group=)
 *  - GET /leagues/:id/matchdays/:n/matches   (?group=&status=)
 *  - GET /leagues/:id/groups
 *  - GET /leagues/:id/siblings
 */
import api, { toApiError } from "./api";

/** Lista de jornadas de una liga (con totales y rango de fechas) */
export async function fetchMatchdays(leagueId, { group } = {}) {
  try {
    const res = await api.get(`/api/leagues/${leagueId}/matchdays`, {
      params: group ? { group } : {},
    });
    // [{ number, matches_count, played_count, date_range:{from,to} }]
    return res.data?.data ?? [];
  } catch (e) {
    throw toApiError(e);
  }
}

/** Partidos de una jornada concreta */
export async function fetchMatchesByMatchday(leagueId, matchdayNumber, { group, status } = {}) {
  try {
    const params = {};
    if (group)  params.group  = group;           // p. ej. "1", "Norte", "Único"
    if (status) params.status = status;          // "scheduled" | "played" | "postponed" | "canceled"
    const res = await api.get(
      `/api/leagues/${leagueId}/matchdays/${matchdayNumber}/matches`,
      { params }
    );
    // array de partidos (ya ordenado por BE)
    return res.data?.data ?? [];
  } catch (e) {
    throw toApiError(e);
  }
}

/** Subgrupos internos dentro de la liga (para selector de grupo interno) */
export async function fetchLeagueGroups(leagueId) {
  try {
    const res = await api.get(`/api/leagues/${leagueId}/groups`);
    // array de strings; si solo hay 1, el FE puede ocultar el selector
    return res.data?.data ?? [];
  } catch {
    // si 404 u otro → lo tratamos como "sin grupos"
    return [];
  }
}

/** Ligas "hermanas" (misma serie/temporada/categoría), para tabs G1/G2/... */
export async function fetchLeagueSiblings(leagueId) {
  try {
    const res = await api.get(`/api/leagues/${leagueId}/siblings`);
    const arr = res.data?.data ?? [];
    // Enriquecemos con número de grupo si el nombre lo trae (… "Grupo N")
    const withNumber = arr.map((r) => {
      const m = /Grupo\s+(\d+)/i.exec(r.name);
      return { id: r.id, name: r.name, groupNumber: m ? parseInt(m[1], 10) : null };
    });
    // Ordenamos por número si ambos lo tienen, si no alfabético
    return withNumber.sort((a, b) => {
      if (a.groupNumber && b.groupNumber) return a.groupNumber - b.groupNumber;
      return String(a.name).localeCompare(String(b.name), "es");
    });
  } catch (e) {
    throw toApiError(e);
  }
}

/** (Opcional) Resumen de liga para cabecera */
export async function fetchLeagueSummary(leagueId) {
  try {
    const res = await api.get(`/api/leagues/${leagueId}`);
    // { id, name, season, category, level, gender, min_matchday, max_matchday, date_range:{from,to} }
    return res.data?.data ?? null;
  } catch (e) {
    throw toApiError(e);
  }
}

export async function getMatchDetail(matchId) {
  try {
    const { data } = await api.get(`/api/matches/${matchId}`);
    return data.data; // { id, league_id, matchday, scheduled_at, status, score, home_team, away_team, venue, events, team_stats?, lineups? }
  } catch (err) {
    throw toApiError(err);
  }
}