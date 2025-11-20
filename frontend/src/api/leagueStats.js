import api from "./api";

/**
 * Stats públicos de una liga:
 * GET /api/leagues/{league}/stats
 */
export async function fetchLeagueStats(leagueId) {
  const res = await api.get(`/api/leagues/${leagueId}/stats`);
  return res.data.data;
}

/**
 * Overrides actuales (solo admin):
 * GET /api/admin/leagues/{league}/stats/overrides
 */
export async function fetchLeagueStatsOverrides(leagueId) {
  const res = await api.get(`/api/admin/leagues/${leagueId}/stats/overrides`);
  return res.data.data;
}

/**
 * Guardar overrides (sync):
 * PUT /api/admin/leagues/{league}/stats/overrides
 */
export async function syncLeagueStatsOverrides(leagueId, payload) {
  const res = await api.put(
    `/api/admin/leagues/${leagueId}/stats/overrides`,
    payload
  );
  return res.data;
}
