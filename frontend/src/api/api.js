import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE,
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: "XSRF-TOKEN",   
  xsrfHeaderName: "X-XSRF-TOKEN", 
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      console.warn("No autorizado. Redirigiendo al login...");
    }
    return Promise.reject(error);
  }
);

export function toApiError(err) {
  if (err?.response) {
    const msg = err.response?.data?.message || err.response?.statusText || "Error de API";
    return new Error(`${msg} (HTTP ${err.response.status})`);
  }
  if (err?.request) return new Error("Error de red o servidor no responde");
  return new Error("Error desconocido");
}

export default api;
