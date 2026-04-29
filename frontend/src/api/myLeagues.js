import api from "./api";

export async function getMyPrivateLeagues(filters = {}) {
  const params = {};

  if (filters.search) params.search = filters.search;
  if (filters.role) params.role = filters.role;
  if (filters.season) params.season = filters.season;

  const res = await api.get("/api/my/leagues", { params });

  return {
    data: Array.isArray(res.data?.data) ? res.data.data : [],
    meta: res.data?.meta || null,
  };
}