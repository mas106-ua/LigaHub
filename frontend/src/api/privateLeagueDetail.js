import api from "./api";

export async function getPrivateLeagueDetail(leagueId) {
  const { data } = await api.get(`/api/private/leagues/${leagueId}/detail`, {
    withCredentials: true,
    headers: { Accept: "application/json" },
  });

  return data?.data;
}
