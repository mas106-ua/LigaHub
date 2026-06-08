import api from "./api";
import { getCsrf } from "../csrf";
import { getCookie } from "./utils";

function xsrfHeaders() {
  const token = getCookie("XSRF-TOKEN");
  return token ? { "X-XSRF-TOKEN": token } : {};
}

export async function previewPrivateLeagueSchedule(leagueId, type = "single") {
  await getCsrf();

  const { data } = await api.post(
    `/api/private/leagues/${leagueId}/schedule/preview`,
    { type },
    {
      withCredentials: true,
      headers: {
        Accept: "application/json",
        ...xsrfHeaders(),
      },
    }
  );

  return data?.data;
}

export async function publishPrivateLeagueSchedule(leagueId, type = "single") {
  await getCsrf();

  const { data } = await api.post(
    `/api/private/leagues/${leagueId}/schedule/publish`,
    { type },
    {
      withCredentials: true,
      headers: {
        Accept: "application/json",
        ...xsrfHeaders(),
      },
    }
  );

  return data;
}
