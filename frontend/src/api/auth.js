// src/api/auth.js
import api from "./api";
import { getCsrf } from "../csrf";
import { getCookie } from "./utils";

// REGISTRO (tu versión)
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

// LOGIN (mismo patrón que register)
export async function loginUser({ email, password }) {
  // 1) csrf
  await getCsrf();

  // 2) cookie
  const token = getCookie("XSRF-TOKEN");

  // 3) login
  const { data } = await api.post(
    "/api/auth/login",
    { email, password },
    {
      headers: {
        "X-XSRF-TOKEN": token,
      },
    }
  );

  // el backend ya devuelve el user
  return data; // { message, user }
}

// OBTENER USUARIO ACTUAL
export async function getCurrentUser() {
  // a veces no hace falta csrf aquí, pero lo dejamos por si acaso
  await getCsrf();

  const { data } = await api.get("/api/user");
  return data;
}

// LOGOUT
export async function logoutUser() {
  await getCsrf();
  const token = getCookie("XSRF-TOKEN");

  await api.post(
    "/api/auth/logout",
    {},
    {
      headers: {
        "X-XSRF-TOKEN": token,
      },
    }
  );
}
