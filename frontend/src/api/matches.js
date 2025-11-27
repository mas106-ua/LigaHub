import api, { toApiError } from "./api";

export async function fetchMatchById(matchId) {
  try {
    const { data } = await api.get(`/api/matches/${matchId}`);
    return data.data;
  } catch (err) {
    throw toApiError(err);
  }
}
