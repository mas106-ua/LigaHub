// src/pages/Login.jsx
import { useState } from "react";
import { useNavigate, Link, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export default function Login() {
  const { login, authLoading } = useAuth();
  const [email, setEmail] = useState("mario@example.com");
  const [password, setPassword] = useState("password123");
  const [error, setError] = useState(null);
  const navigate = useNavigate();
  const location = useLocation();

  // por si venía de /login?next=/dashboard
  const from = location.state?.from?.pathname || "/";

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);

    const res = await login({ email, password });
    if (res.ok) {
      navigate(from, { replace: true });
    } else {
      setError(res.message || "Error de login");
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-100">
      <div className="bg-white shadow rounded p-6 w-full max-w-md">
        <h1 className="text-2xl font-bold mb-4 text-center">Iniciar sesión</h1>
        {error ? (
          <p className="mb-3 text-sm text-red-600">{error}</p>
        ) : null}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm mb-1" htmlFor="email">
              Email
            </label>
            <input
              id="email"
              type="email"
              className="w-full border rounded px-3 py-2"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              autoComplete="email"
            />
          </div>
          <div>
            <label className="block text-sm mb-1" htmlFor="password">
              Contraseña
            </label>
            <input
              id="password"
              type="password"
              className="w-full border rounded px-3 py-2"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              autoComplete="current-password"
            />
          </div>
          <button
            type="submit"
            disabled={authLoading}
            className="w-full bg-slate-800 text-white py-2 rounded hover:bg-slate-900 disabled:opacity-60"
          >
            {authLoading ? "Entrando..." : "Entrar"}
          </button>
        </form>

        <p className="mt-4 text-sm text-center">
          ¿No tienes cuenta?{" "}
          <Link className="text-slate-700 underline" to="/register">
            Regístrate
          </Link>
        </p>
      </div>
    </div>
  );
}
