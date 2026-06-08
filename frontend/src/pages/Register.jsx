import { useState } from "react";
import { Form, Button, Card, Alert, Spinner } from "react-bootstrap";
import { registerUser } from "../api/auth";
import { Link, useLocation, useNavigate } from "react-router-dom";

export default function Register() {
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
  });
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState("");
  const [errors, setErrors] = useState({});

  const handleChange = (e) =>
    setForm({ ...form, [e.target.name]: e.target.value });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage("");
    setErrors({});

    if (!form.email || !form.password || !form.password_confirmation) {
      setErrors({ general: "Todos los campos obligatorios deben completarse." });
      setLoading(false);
      return;
    }

    if (form.password !== form.password_confirmation) {
      setErrors({
        password_confirmation: ["Las contraseñas no coinciden."],
      });
      setLoading(false);
      return;
    }

    try {
      await registerUser(form);
      setMessage("Cuenta creada correctamente.");
      setTimeout(() => {
        navigate("/login", {
          replace: true,
          state: { from: returnTo },
        });
      }, 1200);
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setErrors({ general: "Error inesperado. Inténtalo de nuevo." });
      }
    } finally {
      setLoading(false);
    }
  };

  const emailError =
    typeof errors.email === "string"
      ? errors.email
      : errors.email?.[0] || "";

  const passwordError =
    typeof errors.password === "string"
      ? errors.password
      : errors.password?.[0] || "";

  const passwordConfirmationError =
    typeof errors.password_confirmation === "string"
      ? errors.password_confirmation
      : errors.password_confirmation?.[0] || "";

  const navigate = useNavigate();
  const location = useLocation();

  const fromLocation = location.state?.from;
  const returnTo =
    fromLocation?.pathname &&
    !["/login", "/register"].includes(fromLocation.pathname)
      ? fromLocation
      : { pathname: "/", search: "" };

  return (
    <div className="app-auth-shell">
      <Card className="app-auth-card shadow-lg border-0">
        <div className="app-auth-card__header">
          <h1 className="app-auth-card__title" id="register-title">
            Crear cuenta
          </h1>
          <p className="app-auth-card__subtitle">
            Regístrate para acceder a la plataforma y a tus competiciones.
          </p>
        </div>

        <Card.Body className="app-auth-card__body">
          {message && (
            <Alert variant="success" role="status">
              {message}
            </Alert>
          )}
          {errors.general && (
            <Alert variant="danger" role="alert">
              {errors.general}
            </Alert>
          )}

          <Form onSubmit={handleSubmit} noValidate aria-labelledby="register-title">
            <Form.Group className="mb-3" controlId="register-name">
              <Form.Label>Nombre</Form.Label>
              <Form.Control
                type="text"
                name="name"
                value={form.name}
                onChange={handleChange}
                placeholder="Nombre y apellidos"
                autoComplete="name"
              />
            </Form.Group>

            <Form.Group className="mb-3" controlId="register-email">
              <Form.Label>Correo electrónico</Form.Label>
              <Form.Control
                type="email"
                name="email"
                value={form.email}
                onChange={handleChange}
                placeholder="tu@correo.com"
                autoComplete="email"
                required
                isInvalid={!!emailError}
              />
              {emailError && (
                <Form.Control.Feedback type="invalid">
                  {emailError}
                </Form.Control.Feedback>
              )}
            </Form.Group>

            <Form.Group className="mb-3" controlId="register-password">
              <Form.Label>Contraseña</Form.Label>
              <Form.Control
                type="password"
                name="password"
                value={form.password}
                onChange={handleChange}
                placeholder="********"
                autoComplete="new-password"
                required
                isInvalid={!!passwordError}
              />
              {passwordError && (
                <Form.Control.Feedback type="invalid">
                  {passwordError}
                </Form.Control.Feedback>
              )}
            </Form.Group>

            <Form.Group
              className="mb-4"
              controlId="register-password-confirmation"
            >
              <Form.Label>Confirmar contraseña</Form.Label>
              <Form.Control
                type="password"
                name="password_confirmation"
                value={form.password_confirmation}
                onChange={handleChange}
                placeholder="********"
                autoComplete="new-password"
                required
                isInvalid={!!passwordConfirmationError}
              />
              {passwordConfirmationError && (
                <Form.Control.Feedback type="invalid">
                  {passwordConfirmationError}
                </Form.Control.Feedback>
              )}
            </Form.Group>

            <div className="d-grid">
              <Button variant="primary" type="submit" disabled={loading}>
                {loading ? (
                  <>
                    <Spinner animation="border" size="sm" /> Registrando...
                  </>
                ) : (
                  "Registrarme"
                )}
              </Button>
            </div>
          </Form>

          <p className="text-center mt-3 mb-0 app-auth-card__footer-text">
            ¿Ya tienes cuenta?{" "}
            <Link
              to="/login"
              state={{ from: returnTo }}
              className="fw-semibold text-primary"
            >
              Inicia sesión
            </Link>
          </p>
        </Card.Body>
      </Card>
    </div>
  );
}