import api from "./api";
import { getCsrf } from "../csrf";
import { getCookie } from "./utils";

function xsrfHeaders() {
  const token = getCookie("XSRF-TOKEN");
  return token ? { "X-XSRF-TOKEN": token } : {};
}

export async function getPrivateLeagueMatchdays(leagueId) {
  await getCsrf();

  const { data } = await api.get(
    `/api/private/leagues/${leagueId}/matchdays`,
    {
      withCredentials: true,
      headers: {
        Accept: "application/json",
        ...xsrfHeaders(),
      },
    }
  );

  return data?.data ?? [];
}

export async function getPrivateLeagueMatchesByMatchday(leagueId, matchday) {
  await getCsrf();

  const { data } = await api.get(
    `/api/private/leagues/${leagueId}/matchdays/${matchday}/matches`,
    {
      withCredentials: true,
      headers: {
        Accept: "application/json",
        ...xsrfHeaders(),
      },
    }
  );

  return data?.data ?? [];
}
