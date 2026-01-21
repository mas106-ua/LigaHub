import api from "./api";

export async function getMyPrivateLeagues() {
  const { data } = await api.get("/api/my/leagues", {
    params: { type: "private" },
    withCredentials: true,
    headers: { Accept: "application/json" },
  });

  return data?.data ?? [];
}
