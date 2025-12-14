// src/layouts/AppLayout.jsx
import { useAuth } from "../context/AuthContext";
import { Outlet, Link, useLocation, useNavigate } from "react-router-dom";
import { useEffect, useState } from "react";

const API_BASE = import.meta.env.VITE_API_BASE?.replace(/\/$/, "");

export default function AppLayout() {
  const { user, loading, logout } = useAuth();
  const [open, setOpen] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();

  useEffect(() => { if (open) setOpen(false); }, [location.pathname]);

  const handleLogout = async () => {
    setOpen(false);
    await logout();
    navigate("/ligas", { replace: true });
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
          {/* IZQUIERDA: logo + accesos */}
          <div className="d-flex align-items-center gap-4">
            <Link to="/" className="d-inline-block" title="Inicio">
              <img
                src="/img/logo_rfef.png"
                alt="TFG Fútbol"
                width={30}
                height="auto"
                style={{ display: "block" }}
              />
            </Link>

            {/* Accesos directos públicos */}
            <nav className="d-flex gap-3">
              {/* Listados prefiltrados con el nivel bloqueado */}
              <Link className="text-white-50 text-decoration-none" to="/ligas?level=pro&lock_level=1">
                FÚTBOL PROFESIONAL
              </Link>
              <Link className="text-white-50 text-decoration-none" to="/ligas?level=semi&lock_level=1">
                FÚTBOL SEMIPROFESIONAL
              </Link>
              <Link className="text-white-50 text-decoration-none" to="/ligas?level=amateur&lock_level=1">
                FÚTBOL AMATEUR
              </Link>

              {/* Solo admins: Dashboard (no mostramos Home para invitados) */}
              {!loading && (user?.role === "admin" || user?.role === "superadmin") && (
                <Link className="text-white-50 text-decoration-none" to="/admin/competitions">
                  DASHBOARD
                </Link>
              )}
            </nav>
          </div>

          {/* DERECHA: auth */}
          <div className="position-relative">
            {!user ? (
              <div className="d-flex align-items-center gap-2">
                <Link to="/login" className="btn btn-sm btn-light">Iniciar sesión</Link>
                <Link to="/register" className="btn btn-sm btn-outline-light">Registrarse</Link>
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
                      src={user.avatar_url.startsWith("http") ? user.avatar_url : `${API_BASE}${user.avatar_url}`}
                      alt={user.name}
                      style={{ width: 26, height: 26, borderRadius: "9999px", objectFit: "cover" }}
                    />
                  ) : (
                    <div
                      style={{
                        width: 26, height: 26, borderRadius: "9999px",
                        backgroundColor: "rgba(200,16,46,.12)", display: "flex",
                        alignItems: "center", justifyContent: "center",
                        fontWeight: 700, color: "#C8102E", fontSize: "0.75rem", textTransform: "uppercase",
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
                      position: "absolute", right: 0, top: "110%",
                      background: "#fff", borderRadius: "0.5rem",
                      minWidth: "160px", overflow: "hidden", zIndex: 10,
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
