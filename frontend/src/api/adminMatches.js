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
