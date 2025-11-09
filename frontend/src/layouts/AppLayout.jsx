// src/layouts/AppLayout.jsx
import { useAuth } from "../context/AuthContext";
import { Outlet, NavLink, Link, useLocation, useNavigate } from "react-router-dom";
import { useEffect, useState } from "react";

const API_BASE = import.meta.env.VITE_API_BASE?.replace(/\/$/, "");

export default function AppLayout() {
  const { user, loading, logout } = useAuth();
  const [open, setOpen] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();

  // Cierra el dropdown al cambiar de ruta
  useEffect(() => {
    if (open) setOpen(false);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [location.pathname]);

  const handleLogout = async () => {
    setOpen(false);          // cierra el menú
    await logout();          // destruye sesión y limpia estado
    navigate("/", { replace: true }); // lleva al home
  };

  return (
    <div className="min-vh-100" style={{ backgroundColor: "var(--color-light)" }}>
      {/* NAVBAR */}
      <header
        style={{
          background: "linear-gradient(135deg, #C8102E 0%, #990021 100%)",
          color: "#fff",
        }}
      >
        <div
          className="d-flex align-items-center justify-content-between"
          style={{ maxWidth: "1140px", margin: "0 auto", padding: "0.4rem 1rem" }}
        >
          {/* IZQUIERDA */}
          <div className="d-flex align-items-center gap-4">
            <img
                src="/img/logo_rfef.png"
                alt="TFG Fútbol"
                width={30}     // ajusta a tu gusto
                height="auto"
                style={{ display: "block" }}
            />

            {!loading && user?.role === "superadmin" && (
              <nav className="d-flex gap-3">
                <NavLink
                  to="/"
                  end
                  className={({ isActive }) =>
                    "text-decoration-none " +
                    (isActive
                      ? "text-white fw-semibold border-bottom border-warning pb-1"
                      : "text-white-50 hover:text-white")
                  }
                >
                  Home
                </NavLink>

                  <NavLink
                    to="/dashboard"
                    className={({ isActive }) =>
                      "text-decoration-none " +
                      (isActive
                        ? "text-white fw-semibold border-bottom border-warning pb-1"
                        : "text-white-50 hover:text-white")
                    }
                  >
                    Dashboard
                  </NavLink>
              </nav>
            )}
          </div>

          {/* DERECHA */}
          <div className="position-relative">
            {/* Si NO hay usuario => botones Login / Registro */}
            {!user ? (
              <div className="d-flex align-items-center gap-2">
                <Link to="/login" className="btn btn-sm btn-light">
                  Iniciar sesión
                </Link>
                <Link to="/register" className="btn btn-sm btn-outline-light">
                  Registrarse
                </Link>
              </div>
            ) : (
              <>
                <button
                  type="button"
                  onClick={() => setOpen((v) => !v)}
                  className="d-flex align-items-center gap-2 bg-white text-dark px-3 py-1 rounded-pill border-0"
                  style={{ fontSize: "0.8rem", cursor: "pointer" }}
                >
                  {user?.avatar_url ? (
                    <img
                      src={
                        user.avatar_url.startsWith("http")
                          ? user.avatar_url
                          : `${API_BASE}${user.avatar_url}`
                      }
                      alt={user.name}
                      style={{
                        width: 26,
                        height: 26,
                        borderRadius: "9999px",
                        objectFit: "cover",
                      }}
                    />
                  ) : (
                    <div
                      style={{
                        width: 26,
                        height: 26,
                        borderRadius: "9999px",
                        backgroundColor: "rgba(200,16,46,.12)",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        fontWeight: 700,
                        color: "#C8102E",
                        fontSize: "0.75rem",
                        textTransform: "uppercase",
                      }}
                    >
                      {user?.name ? user.name[0] : "U"}
                    </div>
                  )}

                  <span>{user?.name || "Usuario"}</span>
                  <span style={{ fontSize: "0.7rem" }}>▾</span>
                </button>

                {open && (
                  <div
                    className="shadow-sm"
                    style={{
                      position: "absolute",
                      right: 0,
                      top: "110%",
                      background: "#fff",
                      borderRadius: "0.5rem",
                      minWidth: "160px",
                      overflow: "hidden",
                      zIndex: 10,
                    }}
                  >
                    <Link
                      to="/profile"
                      onClick={() => setOpen(false)}
                      className="w-100 d-block text-decoration-none text-dark px-3 py-2"
                      style={{ fontSize: "0.85rem" }}
                    >
                      Ver perfil
                    </Link>

                    <button
                      onClick={handleLogout}
                      className="w-100 text-start px-3 py-2 bg-white border-0"
                      style={{ fontSize: "0.85rem" }}
                    >
                      Cerrar sesión
                    </button>
                  </div>
                )}
              </>
            )}
          </div>
        </div>
      </header>

      {/* CONTENIDO */}
      <main style={{ maxWidth: "1140px", margin: "1.5rem auto", padding: "0 1rem 2rem" }}>
        <Outlet />
      </main>
    </div>
  );
}
