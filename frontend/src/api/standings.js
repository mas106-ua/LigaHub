import api from "./api";

/**
 * GET /api/leagues/{league}/standings?group=&matchday=
 */
export async function getStandings(leagueId, params = {}) {
  const { data } = await api.get(`/api/leagues/${leagueId}/standings`, {
    params,
  });

  // El backend devuelve { data: { ... } }
  return data.data ?? data;
}
