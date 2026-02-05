import api from "./api";
import { getCsrf } from "../csrf";
import { getCookie } from "./utils";

function xsrfHeaders() {
  const token = getCookie("XSRF-TOKEN");
  return token ? { "X-XSRF-TOKEN": token } : {};
}

// ✅ Check sin ensuciar lógica: 200 => existe, 404 => no existe
export async function checkPrivateMatchReport(matchId) {
  await getCsrf();
  const res = await api.get(`/api/private/matches/${matchId}/report`, {
    responseType: "blob",
    withCredentials: true,
    headers: { Accept: "application/pdf", ...xsrfHeaders() },
    validateStatus: (s) => s === 200 || s === 404,
  });

  return res.status === 200;
}

export async function downloadPrivateMatchReport(matchId) {
  await getCsrf();
  return api.get(`/api/private/matches/${matchId}/report`, {
    responseType: "blob",
    withCredentials: true,
    headers: { Accept: "application/pdf", ...xsrfHeaders() },
  });
}

export async function generatePrivateMatchReport(matchId) {
  await getCsrf();
  const { data } = await api.post(
    `/api/private/matches/${matchId}/report`,
    {},
    {
      withCredentials: true,
      headers: { Accept: "application/json", ...xsrfHeaders() },
    }
  );
  return data;
}
