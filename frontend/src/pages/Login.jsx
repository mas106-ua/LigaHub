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
  const from = location.state?.from?.pathname || "/";

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);

    const res = await login({ email, password });
    if (res.ok) {
      navigate(from, { replace: true });
    } else {
      setError(res.message || "Error de inicio de sesión");
    }
  }

  return (
    <div
      className="d-flex justify-content-center align-items-center"
      style={{ minHeight: "100vh", backgroundColor: "var(--color-light)" }}
    >
      <div
        className="shadow-lg"
        style={{
          width: "100%",
          maxWidth: "440px",
          background: "#fff",
          borderRadius: "1.25rem",
        }}
      >
        <div
          style={{
            background: "linear-gradient(135deg, #C8102E 0%, #990021 100%)",
            color: "#fff",
            borderTopLeftRadius: "1.25rem",
            borderTopRightRadius: "1.25rem",
            padding: "1.75rem 1.5rem 1.5rem",
          }}
        >
          <h3 className="mb-1 fw-semibold">Iniciar sesión</h3>
        </div>

        <div className="p-4">
          {error && <p className="text-danger small mb-3">{error}</p>}
          <form onSubmit={handleSubmit}>
            <div className="mb-3">
              <label className="form-label">Email</label>
              <input
                type="email"
                className="form-control"
                placeholder="tu@correo.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>

            <div className="mb-4">
              <label className="form-label">Contraseña</label>
              <input
                type="password"
                className="form-control"
                placeholder="********"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
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

          <p className="text-center mt-3 mb-0" style={{ fontSize: "0.9rem" }}>
            ¿No tienes cuenta?{" "}
            <Link to="/register" className="fw-semibold text-primary">
              Regístrate
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}
