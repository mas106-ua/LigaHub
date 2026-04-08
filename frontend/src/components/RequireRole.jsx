import { Navigate, Outlet } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export default function RequireRole({ roles, children }) {
  const { user, isAuth, loading } = useAuth();

  if (loading) {
    return (
      <div className="container py-4" role="status" aria-live="polite">
        Comprobando permisos...
      </div>
    );
  }

  if (!isAuth) {
    return <Navigate to="/login" replace />;
  }

  const allowed = Array.isArray(roles)
    ? roles.includes(user?.role)
    : user?.role === roles;

  if (!allowed) {
    return <Navigate to="/forbidden" replace />;
  }

  if (children) {
    return children;
  }

  return <Outlet />;
}