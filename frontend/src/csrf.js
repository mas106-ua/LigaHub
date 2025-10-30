import api from "./api/api";

export async function getCsrf() {
  await api.get("/sanctum/csrf-cookie");
}
