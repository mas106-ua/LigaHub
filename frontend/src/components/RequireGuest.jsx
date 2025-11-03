import { Navigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export default function RequireGuest({ children }) {
  const { isAuth, loading } = useAuth();
  if (loading) return null; // o un spinner
  return isAuth ? <Navigate to="/" replace /> : children;
}
