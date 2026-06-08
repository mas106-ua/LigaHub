import { useState } from "react";
import { Alert, Button, Card, Form, Spinner } from "react-bootstrap";
import { useNavigate } from "react-router-dom";
import { createPrivateLeague } from "../api/privateLeagues";

function firstError(err) {
  if (!err) return "";
  if (typeof err === "string") return err;
  if (Array.isArray(err)) return err[0] || "";
  return "";
}

export default function MyLeaguesCreatePage() {
  const navigate = useNavigate();

  const [name, setName] = useState("");
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const onSubmit = async (e) => {
    e.preventDefault();
    setErrors({});

    const trimmed = name.trim();
    if (!trimmed) {
      setErrors({ name: ["El nombre es obligatorio."] });
      return;
    }

    setLoading(true);
    try {
      await createPrivateLeague({ name: trimmed });

      navigate("/mis-ligas", {
        replace: true,
        state: { flash: "Liga privada creada." },
      });
    } catch (err) {
      if (err?.response?.status === 422) {
        setErrors(
          err.response.data?.errors || {
            general: ["Validación incorrecta."],
          }
        );
      } else {
        setErrors({
          general: ["No se pudo crear la liga. Inténtalo de nuevo."],
        });
      }
    } finally {
      setLoading(false);
    }
  };

  const nameError = firstError(errors.name);
  const generalError = firstError(errors.general);

  return (
    <div className="d-flex justify-content-center">
      <Card className="shadow-sm border-0 app-accessibility-card app-form-card">
        <div className="app-auth-card__header">
          <h1 className="app-auth-card__title">Crear liga privada</h1>
          <p className="app-auth-card__subtitle">
            Define el nombre inicial de la liga para empezar su configuración.
          </p>
        </div>

        <Card.Body className="p-4">
          {generalError && (
            <Alert variant="danger" role="alert">
              {generalError}
            </Alert>
          )}

          <Form onSubmit={onSubmit} noValidate>
            <Form.Group className="mb-3" controlId="league-name">
              <Form.Label>Nombre de la liga</Form.Label>
              <Form.Control
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Nombre de la liga"
                isInvalid={!!nameError}
                disabled={loading}
                autoFocus
                required
              />
              {nameError && (
                <Form.Control.Feedback type="invalid">
                  {nameError}
                </Form.Control.Feedback>
              )}
            </Form.Group>

            <div className="app-form-actions">
              <Button
                variant="secondary"
                type="button"
                disabled={loading}
                onClick={() => navigate("/mis-ligas")}
              >
                Cancelar
              </Button>

              <Button variant="primary" type="submit" disabled={loading}>
                {loading ? (
                  <>
                    <Spinner animation="border" size="sm" /> Creando...
                  </>
                ) : (
                  "Crear"
                )}
              </Button>
            </div>
          </Form>
        </Card.Body>
      </Card>
    </div>
  );
}