import api, { toApiError } from "./api";

/**
 * Devuelve la plantilla de un equipo:
 * [{ id, name, shirt_number?, position? }, ...]
 */
export async function fetchTeamPlayers(teamId) {
  try {
    const { data } = await api.get(`/api/teams/${teamId}/players`);
    return data;
  } catch (err) {
    throw toApiError(err);
  }
}
