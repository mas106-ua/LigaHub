// src/layouts/AppLayout.jsx
import { useAuth } from "../context/AuthContext";
import { Outlet, NavLink } from "react-router-dom";

export default function AppLayout() {
  const { user, logout } = useAuth();

  return (
    <div className="min-h-screen bg-slate-100">
      <header className="bg-white border-b">
        <div className="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <span className="font-bold text-slate-800">TFG Fútbol</span>
            <nav className="flex gap-3 text-sm">
              <NavLink
                to="/"
                className={({ isActive }) =>
                  isActive ? "text-slate-900 font-semibold" : "text-slate-500"
                }
                end
              >
                Home
              </NavLink>
              <NavLink
                to="/dashboard"
                className={({ isActive }) =>
                  isActive ? "text-slate-900 font-semibold" : "text-slate-500"
                }
              >
                Dashboard
              </NavLink>
              {/* aquí meterás más vistas privadas */}
            </nav>
          </div>
          <div className="flex items-center gap-3">
            <span className="text-sm text-slate-600">
              {user ? user.email : ""}
            </span>
            <button
              onClick={logout}
              className="text-sm bg-slate-800 text-white px-3 py-1 rounded"
            >
              Cerrar sesión
            </button>
          </div>
        </div>
      </header>

      <main className="max-w-6xl mx-auto px-4 py-6">
        <Outlet />
      </main>
    </div>
  );
}
