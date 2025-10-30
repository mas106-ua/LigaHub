// src/auth.js
import api from "./api";
import { getCsrf } from "./csrf";
import { getCookie } from "./utils";

export async function registerUser(payload) {
  // 1) primero pedimos las cookies a Laravel
  await getCsrf();

  // 2) leemos la cookie XSRF-TOKEN que puso Laravel
  const token = getCookie("XSRF-TOKEN");

  // 3) hacemos el POST y mandamos el header a mano
  const { data } = await api.post("/api/auth/register", payload, {
    headers: {
      "X-XSRF-TOKEN": token,
    },
  });

  return data;
}
