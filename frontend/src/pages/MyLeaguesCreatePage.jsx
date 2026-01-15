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
        setErrors(err.response.data?.errors || { general: ["Validación incorrecta."] });
      } else {
        setErrors({ general: ["No se pudo crear la liga. Inténtalo de nuevo."] });
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="d-flex justify-content-center">
      <Card className="shadow-sm border-0" style={{ maxWidth: 560, width: "100%", borderRadius: "1rem" }}>
        <div
          style={{
            background: "linear-gradient(135deg, #C8102E 0%, #990021 100%)",
            color: "#fff",
            borderTopLeftRadius: "1rem",
            borderTopRightRadius: "1rem",
            padding: "1.25rem 1.25rem 1rem",
          }}
        >
          <h4 className="mb-0 fw-semibold">Crear liga privada</h4>
          <small className="text-white-50">Formulario básico</small>
        </div>

        <Card.Body className="p-4">
          {firstError(errors.general) && <Alert variant="danger">{firstError(errors.general)}</Alert>}

          <Form onSubmit={onSubmit}>
            <Form.Group className="mb-3" controlId="leagueName">
              <Form.Label>Nombre</Form.Label>
              <Form.Control
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Nombre de la liga"
                isInvalid={!!firstError(errors.name)}
                disabled={loading}
                autoFocus
              />
              {firstError(errors.name) && (
                <Form.Control.Feedback type="invalid">{firstError(errors.name)}</Form.Control.Feedback>
              )}
            </Form.Group>

            <div className="d-flex gap-2">
              <Button variant="secondary" type="button" disabled={loading} onClick={() => navigate("/mis-ligas")}>
                Cancelar
              </Button>
              <Button variant="primary" type="submit" disabled={loading} className="ms-auto">
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
