import api from "./api";
import { getCsrf } from "../csrf";
import { getCookie } from "./utils";

function xsrfHeaders() {
  const token = getCookie("XSRF-TOKEN");
  return { "X-XSRF-TOKEN": token, Accept: "application/json" };
}

export async function getPrivateLeaguePlayers(leagueId, teamId) {
  const { data } = await api.get(`/api/private/leagues/${leagueId}/players`, {
    params: teamId ? { team_id: teamId } : {},
    withCredentials: true,
    headers: { Accept: "application/json" },
  });

  return data?.data ?? [];
}

export async function createPrivateLeaguePlayer(leagueId, payload) {
  await getCsrf();
  const { data } = await api.post(`/api/private/leagues/${leagueId}/players`, payload, {
    withCredentials: true,
    headers: xsrfHeaders(),
  });
  return data?.data;
}

export async function updatePrivateLeaguePlayer(leagueId, playerId, payload) {
  await getCsrf();
  const { data } = await api.put(`/api/private/leagues/${leagueId}/players/${playerId}`, payload, {
    withCredentials: true,
    headers: xsrfHeaders(),
  });
  return data?.data;
}

export async function deletePrivateLeaguePlayer(leagueId, playerId) {
  await getCsrf();
  const { data } = await api.delete(`/api/private/leagues/${leagueId}/players/${playerId}`, {
    withCredentials: true,
    headers: xsrfHeaders(),
  });
  return data;
}
