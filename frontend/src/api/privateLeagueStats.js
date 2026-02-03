import api from "./api";
import { getCsrf } from "../csrf";
import { getCookie } from "./utils";

function xsrfHeaders() {
  const token = getCookie("XSRF-TOKEN");
  return token ? { "X-XSRF-TOKEN": token } : {};
}

export async function getPrivateLeagueStats(leagueId, params = {}) {
  await getCsrf();

  const { data } = await api.get(
    `/api/private/leagues/${leagueId}/stats`,
    {
      params,
      withCredentials: true,
      headers: {
        Accept: "application/json",
        ...xsrfHeaders(),
      },
    }
  );

  return data?.data ?? {};
}
