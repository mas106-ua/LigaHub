import { Navigate, Outlet } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export default function RequireRole({ roles, children }) {
  const { user, isAuth, loading } = useAuth();
  // espera a que termine la carga inicial
  if (loading) return <div>Cargando...</div>;
  if (!isAuth) return <Navigate to="/login" replace />;
  const allowed = Array.isArray(roles) ? roles.includes(user?.role) : user?.role === roles;
  return allowed ? children : <Navigate to="/forbidden" replace />;
}

