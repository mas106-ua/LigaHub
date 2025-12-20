import api from "./api";

/**
 * GET /api/leagues/{league}/versions
 */
export async function fetchLeagueVersions(leagueId) {
  const res = await api.get(`/api/leagues/${leagueId}/versions`);
  // Backend devuelve: { competition, versions: [...] }
  return res.data;
}
