import api from "./api";

/**
 * GET /api/leagues/{league}/detail
 */
export async function fetchLeagueDetail(leagueId) {
  const res = await api.get(`/api/leagues/${leagueId}/detail`);
  return res.data.data;
}
