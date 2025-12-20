import api from "./api";

/**
 * Admin – Official competitions
 *
 * Backend endpoints (BE-REF-08):
 *  - GET /api/admin/competitions
 *  - GET /api/admin/competitions/{competition}/leagues
 */

export async function fetchAdminCompetitions(params = {}, config = {}) {
  const res = await api.get("/api/admin/competitions", {
    ...config,
    params,
  });
  return res.data;
}

export async function fetchAdminCompetitionLeagues(competitionId, config = {}) {
  const res = await api.get(`/api/admin/competitions/${competitionId}/leagues`, {
    ...config,
  });
  return res.data;
}
