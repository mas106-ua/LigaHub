import api from "./api";
import { getCsrf } from "../csrf";
import { getCookie } from "./utils";

function xsrfHeaders() {
  const token = getCookie("XSRF-TOKEN");
  return { "X-XSRF-TOKEN": token, Accept: "application/json" };
}

export async function getPrivateLeagueTeams(leagueId) {
  const { data } = await api.get(`/api/private/leagues/${leagueId}/teams`, {
    withCredentials: true,
    headers: { Accept: "application/json" },
  });
  return data?.data ?? [];
}

export async function createPrivateLeagueTeam(leagueId, payload) {
  await getCsrf();
  const { data } = await api.post(`/api/private/leagues/${leagueId}/teams`, payload, {
    withCredentials: true,
    headers: xsrfHeaders(),
  });
  return data?.data;
}

export async function updatePrivateLeagueTeam(leagueId, teamId, payload) {
  await getCsrf();
  const { data } = await api.put(`/api/private/leagues/${leagueId}/teams/${teamId}`, payload, {
    withCredentials: true,
    headers: xsrfHeaders(),
  });
  return data?.data;
}

export async function deletePrivateLeagueTeam(leagueId, teamId) {
  await getCsrf();
  const { data } = await api.delete(`/api/private/leagues/${leagueId}/teams/${teamId}`, {
    withCredentials: true,
    headers: xsrfHeaders(),
  });
  return data;
}
