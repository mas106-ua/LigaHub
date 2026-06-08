import { useState } from "react";
import { useNavigate, Link, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { Spinner } from "react-bootstrap";

export default function Login() {
  const { login, authLoading } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState(null);
  const navigate = useNavigate();
  const location = useLocation();
  const fromLocation = location.state?.from;

  const redirectTo =
    fromLocation?.pathname &&
    !["/login", "/register"].includes(fromLocation.pathname)
      ? `${fromLocation.pathname}${fromLocation.search || ""}`
      : "/";

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);

    const res = await login({ email, password });
    if (res.ok) {
      navigate(redirectTo, { replace: true });
    } else {
      setError(res.message || "Error de inicio de sesión");
    }
  }

  return (
    <div className="app-auth-shell">
      <section
        className="app-auth-card shadow-lg"
        aria-labelledby="login-title"
      >
        <div className="app-auth-card__header">
          <h1 id="login-title" className="app-auth-card__title">
            Iniciar sesión
          </h1>
          <p className="app-auth-card__subtitle">
            Accede a tu cuenta para gestionar tu perfil y tus ligas.
          </p>
        </div>

        <div className="app-auth-card__body">
          {error && (
            <div className="alert alert-danger mb-3" role="alert">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} noValidate>
            <div className="mb-3">
              <label htmlFor="login-email" className="form-label">
                Correo electrónico
              </label>
              <input
                id="login-email"
                type="email"
                className="form-control"
                placeholder="tu@correo.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                autoComplete="username"
                required
              />
            </div>

            <div className="mb-4">
              <label htmlFor="login-password" className="form-label">
                Contraseña
              </label>
              <input
                id="login-password"
                type="password"
                className="form-control"
                placeholder="********"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                autoComplete="current-password"
                required
              />
            </div>

            <button
              type="submit"
              className="btn btn-primary w-100"
              disabled={authLoading}
            >
              {authLoading ? (
                <>
                  <Spinner animation="border" size="sm" /> Entrando...
                </>
              ) : (
                "Entrar"
              )}
            </button>
          </form>

          <p className="text-center mt-3 mb-0 app-auth-card__footer-text">
            ¿No tienes cuenta?{" "}
            <Link
              to="/register"
              state={{ from: fromLocation || { pathname: "/", search: "" } }}
              className="fw-semibold text-primary"
            >
              Regístrate
            </Link>
          </p>
        </div>
      </section>
    </div>
  );
}