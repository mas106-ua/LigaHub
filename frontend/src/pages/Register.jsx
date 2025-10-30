import { useState } from "react";
import { Form, Button, Card, Alert, Spinner } from "react-bootstrap";
import { registerUser } from "../api/auth";

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

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage("");
    setErrors({});

    if (!form.email || !form.password || !form.password_confirmation) {
      setErrors({ general: "Todos los campos son obligatorios." });
      setLoading(false);
      return;
    }

    if (form.password !== form.password_confirmation) {
      setErrors({ password: "Las contraseñas no coinciden." });
      setLoading(false);
      return;
    }

    try {
      await registerUser(form);
      setMessage("Cuenta creada correctamente.");
      setTimeout(() => (window.location.href = "/login"), 2000);
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

  return (
    <div className="d-flex justify-content-center align-items-center" style={{ minHeight: "100vh" }}>
      <Card className="shadow-lg border-0" style={{ maxWidth: "440px", width: "100%", borderRadius: "1.25rem" }}>
        {/* Header completamente arriba */}
        <div
          style={{
            background: "linear-gradient(135deg, #C8102E 0%, #990021 100%)",
            color: "#fff",
            borderTopLeftRadius: "1.25rem",
            borderTopRightRadius: "1.25rem",
            padding: "1.75rem 1.5rem 1.5rem",
          }}
        >
          <h3 className="mb-1 fw-semibold">Crear cuenta</h3>
        </div>

        <Card.Body className="p-4">
          {message && <Alert variant="success">{message}</Alert>}
          {errors.general && <Alert variant="danger">{errors.general}</Alert>}

          <Form onSubmit={handleSubmit}>
            <Form.Group className="mb-3" controlId="name">
              <Form.Label>Nombre</Form.Label>
              <Form.Control
                type="text"
                name="name"
                value={form.name}
                onChange={handleChange}
                placeholder="Nombre y apellidos"
              />
            </Form.Group>

            <Form.Group className="mb-3" controlId="email">
            <Form.Label>Email</Form.Label>
            <Form.Control
                type="email"
                name="email"
                value={form.email}
                onChange={handleChange}
                placeholder="tu@correo.com"
                required
                isInvalid={!!errors.email}
            />
            {errors.email && (
                <Form.Control.Feedback type="invalid">
                {typeof errors.email === "string"
                    ? errors.email
                    : errors.email[0] || "Email no válido."}
                </Form.Control.Feedback>
            )}
            </Form.Group>

            <Form.Group className="mb-3" controlId="password">
            <Form.Label>Contraseña</Form.Label>
            <Form.Control
                type="password"
                name="password"
                value={form.password}
                onChange={handleChange}
                placeholder="********"
                required
                isInvalid={!!errors.password}
            />
            {errors.password && (
                <Form.Control.Feedback type="invalid">
                {typeof errors.password === "string"
                    ? errors.password
                    : errors.password[0] || "Contraseña no válida."}
                </Form.Control.Feedback>
            )}
            </Form.Group>

            <Form.Group className="mb-4" controlId="password_confirmation">
              <Form.Label>Confirmar contraseña</Form.Label>
              <Form.Control
                type="password"
                name="password_confirmation"
                value={form.password_confirmation}
                onChange={handleChange}
                placeholder="********"
                required
              />
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

          <p className="text-center mt-3 mb-0" style={{ fontSize: "0.9rem", color: "#6b7280" }}>
            ¿Ya tienes cuenta?{" "}
            <a href="/login" className="fw-semibold text-primary">
              Inicia sesión
            </a>
          </p>
        </Card.Body>
      </Card>
    </div>
  );
}
