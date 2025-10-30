import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE,
  withCredentials: true,
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

export default api;
