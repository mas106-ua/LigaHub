import api from "./api";

export async function getCsrf() {
  await api.get("/sanctum/csrf-cookie");
}
