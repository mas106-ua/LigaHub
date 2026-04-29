import api from "./api";

export async function resolvePrivateLeagueInvitation(token) {
  const res = await api.get(
    `/api/private-league-invitations/${encodeURIComponent(token)}`
  );

  return res.data?.data;
}

export async function acceptPrivateLeagueInvitation(token) {
  const res = await api.post(
    `/api/private-league-invitations/${encodeURIComponent(token)}/accept`
  );

  return res.data?.data;
}