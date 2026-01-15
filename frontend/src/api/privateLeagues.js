// src/api/privateLeagues.js
import api from "./api";
import { getCsrf } from "../csrf";
import { getCookie } from "./utils";

export async function createPrivateLeague({ name, season_id } = {}) {
  await getCsrf();
  const token = getCookie("XSRF-TOKEN");

  const payload = {
    name: String(name || "").trim(),
    ...(season_id ? { season_id } : {}),
  };

  const { data } = await api.post("/api/private/leagues", payload, {
    headers: { "X-XSRF-TOKEN": token },
    withCredentials: true,
  });

  return data;
}
