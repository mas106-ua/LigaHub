// src/api/matchReports.js
import api, { toApiError } from "./api";
import { getCsrf } from "../csrf";
import { getCookie } from "./utils";

/**
 * GET público: info del acta (si existe) o null si no hay.
 */
export async function getMatchReport(matchId) {
  try {
    const { data } = await api.get(`/api/matches/${matchId}/report`);
    // backend responde { data: { ... } } o 404
    return data?.data ?? null;
  } catch (err) {
    if (err?.response?.status === 404) {
      // no hay acta
      return null;
    }
    throw toApiError(err);
  }
}

/**
 * POST admin: subir / actualizar el PDF de acta.
 */
export async function uploadMatchReport(matchId, file) {
  try {
    // 1) cookie CSRF de Sanctum
    await getCsrf();
    const token = getCookie("XSRF-TOKEN");

    // 2) FormData con el archivo
    const form = new FormData();
    form.append("file", file);

    // 3) POST con X-XSRF-TOKEN y credenciales
    const { data } = await api.post(
      `/api/admin/matches/${matchId}/report`,
      form,
      {
        headers: {
          "X-XSRF-TOKEN": token,
          "Content-Type": "multipart/form-data",
        },
        withCredentials: true,
      }
    );

    return data?.data ?? null;
  } catch (err) {
    throw toApiError(err);
  }
}

/**
 * DELETE admin (opcional): borrar acta.
 */
export async function deleteMatchReport(matchId) {
  try {
    await getCsrf();
    const token = getCookie("XSRF-TOKEN");

    const { data } = await api.delete(
      `/api/admin/matches/${matchId}/report`,
      {
        headers: {
          "X-XSRF-TOKEN": token,
        },
        withCredentials: true,
      }
    );

    return data;
  } catch (err) {
    throw toApiError(err);
  }
}
