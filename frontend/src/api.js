import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE, // se leerá del .env
  withCredentials: true, // necesario para enviar cookies Sanctum
});

// Interceptor opcional para manejar 401 (no autorizado)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      console.warn("No autorizado. Redirigiendo al login...");
      // window.location.href = "/login"; // si usas React Router
    }
    return Promise.reject(error);
  }
);

export default api;
