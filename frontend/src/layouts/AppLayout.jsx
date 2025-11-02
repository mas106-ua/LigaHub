import { useAuth } from "../context/AuthContext";
import { Outlet, NavLink } from "react-router-dom";
import { useState } from "react";

export default function AppLayout() {
  const { user, loading, logout } = useAuth();
  const [open, setOpen] = useState(false);

  return (
    <div
      className="min-vh-100"
      style={{ backgroundColor: "var(--color-light)" }}
    >
      {/* NAVBAR */}
      <header
        style={{
          background: "linear-gradient(135deg, #C8102E 0%, #990021 100%)",
          color: "#fff",
        }}
      >
        <div
          className="d-flex align-items-center justify-content-between"
          style={{
            maxWidth: "1140px",
            margin: "0 auto",
            padding: "0.4rem 1rem", // 👈 más fino
          }}
        >
          {/* IZQUIERDA: LOGO + LINKS */}
          <div className="d-flex align-items-center gap-4">
            {/* Logo / nombre */}
            <span className="fw-bold" style={{ fontSize: "1.05rem" }}>
              TFG Fútbol
            </span>

            {/* Links */}
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

              {/* Solo mostrar cuando ya se cargó el user y es superadmin */}
              {!loading && user?.role === "superadmin" && (
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
              )}
            </nav>
          </div>

          {/* DERECHA: USER DROPDOWN */}
          <div className="position-relative">
            <button
              type="button"
              onClick={() => setOpen((v) => !v)}
              className="d-flex align-items-center gap-2 bg-white text-dark px-3 py-1 rounded-pill border-0"
              style={{ fontSize: "0.8rem", cursor: "pointer" }}
            >
              <div
                style={{
                  width: "26px",
                  height: "26px",
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
                <button
                  onClick={logout}
                  className="w-100 text-start px-3 py-2 bg-white border-0"
                  style={{ fontSize: "0.85rem" }}
                >
                  Cerrar sesión
                </button>
              </div>
            )}
          </div>
        </div>
      </header>

      {/* CONTENIDO */}
      <main
        style={{
          maxWidth: "1140px",
          margin: "1.5rem auto",
          padding: "0 1rem 2rem",
        }}
      >
        <Outlet />
      </main>
    </div>
  );
}
