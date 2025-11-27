// src/components/RequireAuth.jsx
import { Navigate, Outlet, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export default function RequireAuth({ children }) {
  const { user, loading } = useAuth();
  const location = useLocation();

  // Mientras carga el estado de auth (opcional, puedes poner un spinner)
  if (loading) {
    return null; // o <div>Cargando...</div>
  }

  // Si no hay usuario → redirigir a login
  if (!user) {
    return (
      <Navigate
        to="/login"
        replace
        state={{ from: location }}
      />
    );
  }

  // Si se usa como wrapper <RequireAuth>{children}</RequireAuth>
  if (children) {
    return children;
  }

  // Si se usa como <Route element={<RequireAuth />}> ... </Route>
  return <Outlet />;
}
