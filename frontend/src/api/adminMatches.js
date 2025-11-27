// src/api/adminMatches.js
import api, { toApiError } from "./api";
import { getCsrf } from "../csrf";
import { getCookie } from "./utils";

/**
 * Actualiza los resultados de una jornada (admin).
 */
export async function updateMatchdayResults(leagueId, matchdayNumber, matches) {
  try {
    // 1) Aseguramos cookie CSRF
    await getCsrf();

    // 2) Leemos el token de la cookie
    const token = getCookie("XSRF-TOKEN");

    // 3) Preparamos payload
    const payload = {
      matches: matches.map((m) => ({
        id: m.id,
        home_goals: m.home_goals,
        away_goals: m.away_goals,
        status: m.status,
      })),
    };

    // 4) Hacemos el PUT mandando el header X-XSRF-TOKEN
    const { data } = await api.put(
      `/api/admin/leagues/${leagueId}/matchdays/${matchdayNumber}/results`,
      payload,
      {
        headers: { "X-XSRF-TOKEN": token },
        withCredentials: true, // por claridad, aunque ya lo tiene api
      }
    );

    return data; // { message, updated }
  } catch (err) {
    throw toApiError(err);
  }
}

/**
 * Guarda/actualiza las alineaciones de un partido (admin).
 *
 * @param {number|string} matchId
 * @param {{home?: object, away?: object}} payload
 */
export async function updateMatchLineups(matchId, payload) {
  try {
    await getCsrf();
    const token = getCookie("XSRF-TOKEN");

    const { data } = await api.put(
      `/api/admin/matches/${matchId}/lineups`,
      payload,
      {
        headers: { "X-XSRF-TOKEN": token },
        withCredentials: true,
      }
    );

    return data; // { message: 'Alineaciones guardadas correctamente.' }
  } catch (err) {
    throw toApiError(err);
  }
}

// ...

/**
 * Sincroniza los eventos de un partido (admin).
 * Enviamos side, minute, type, player_id, related_player_id, detail.
 */
export async function updateMatchEvents(matchId, events) {
  try {
    await getCsrf();
    const token = getCookie("XSRF-TOKEN");

    const payload = {
      events: events.map((ev) => ({
        id: ev.id ?? null,
        side: ev.side,
        minute:
          ev.minute === "" || ev.minute === null
            ? 0
            : Number(ev.minute),
        type: ev.type,
        player_id: ev.player_id || null,
        related_player_id: ev.related_player_id || null,
        detail: ev.detail || null,
      })),
    };

    const { data } = await api.put(
      `/api/admin/matches/${matchId}/events`,
      payload,
      {
        headers: { "X-XSRF-TOKEN": token },
        withCredentials: true,
      }
    );

    return data;
  } catch (err) {
    throw toApiError(err);
  }
}

export async function openMatch(matchId) {
  try {
    await getCsrf();
    const token = getCookie("XSRF-TOKEN");

    const { data } = await api.post(
      `/api/admin/matches/${matchId}/open`,
      {},
      {
        headers: { "X-XSRF-TOKEN": token },
        withCredentials: true,
      }
    );

    return data; // { message: 'Partido reabierto...' }
  } catch (err) {
    throw toApiError(err);
  }
}

export async function closeMatch(matchId) {
  try {
    await getCsrf();
    const token = getCookie("XSRF-TOKEN");

    const { data } = await api.post(
      `/api/admin/matches/${matchId}/close`,
      {},
      {
        headers: { "X-XSRF-TOKEN": token },
        withCredentials: true,
      }
    );

    return data; // { message: 'Partido cerrado...' }
  } catch (err) {
    throw toApiError(err);
  }
}

export async function verifyMatch(matchId) {
  try {
    await getCsrf();
    const token = getCookie("XSRF-TOKEN");

    const { data } = await api.post(
      `/api/admin/matches/${matchId}/verify`,
      {},
      {
        headers: { "X-XSRF-TOKEN": token },
        withCredentials: true,
      }
    );

    return data; // { message: 'Partido verificado...' }
  } catch (err) {
    throw toApiError(err);
  }
}
