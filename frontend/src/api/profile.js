// src/api/profile.js
import api from "./api";
import { getCsrf } from "../csrf";
import { getCookie } from "./utils";

// GET perfil
export async function getProfile() {
  await getCsrf();
  const { data } = await api.get("/api/profile", { withCredentials: true });
  return data;
}

// PUT perfil (nombre/imagen)
export async function updateProfile({ name, avatarFile }) {
  await getCsrf();
  const token = getCookie("XSRF-TOKEN");
  const form = new FormData();
  // importante: trim para no mandar vacío por despiste
  if (name != null) form.append("name", String(name).trim());
  if (avatarFile) form.append("avatar", avatarFile);

  const { data } = await api.post("/api/profile?_method=PUT", form, {
    withCredentials: true,
    headers: { "X-XSRF-TOKEN": token },
  });

  return data; // { user: {...} }
}

// PUT contraseña
export async function updatePassword({ current_password, password, password_confirmation }) {
  await getCsrf();
  const token = getCookie("XSRF-TOKEN");

  const { data } = await api.put(
    "/api/profile/password",
    { current_password, password, password_confirmation },
    { headers: { "X-XSRF-TOKEN": token }, withCredentials: true }
  );

  return data;
}
