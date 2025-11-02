// src/pages/Forbidden.jsx
import { Link } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

/**
 * Page shown when a user navigates to a route that they do not
 * have permission to view.  Displays a simple message and a link
 * back to the home page.
 */
export default function Forbidden() {
  const { user } = useAuth();
  return (
    <div className="container text-center" style={{ padding: "4rem 0" }}>
      <h1 className="mb-3">Acceso denegado</h1>
      <p className="mb-4">
        {user
          ? "No tienes permisos para acceder a esta sección."
          : "Debes iniciar sesión para acceder a esta sección."}
      </p>
      <Link to="/" className="btn btn-primary">
        Volver al inicio
      </Link>
    </div>
  );
}