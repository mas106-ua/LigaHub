import { Alert, Card, Button } from "react-bootstrap";
import { Link, useLocation } from "react-router-dom";

export default function MyLeaguesPage() {
  const location = useLocation();
  const flash = location.state?.flash;

  return (
    <div className="d-flex justify-content-center">
      <Card className="shadow-sm border-0" style={{ maxWidth: 720, width: "100%", borderRadius: "1rem" }}>
        <Card.Body className="p-4">
          {flash && <Alert variant="success">{flash}</Alert>}

          <div className="d-flex align-items-center justify-content-between gap-3">
            <div>
              <h4 className="mb-1">Mis ligas</h4>
              <div className="text-muted" style={{ fontSize: "0.95rem" }}>
                Aquí verás tus ligas privadas.
              </div>
            </div>

            <Button as={Link} to="/mis-ligas/crear" variant="primary">
              Crear liga
            </Button>
          </div>

          <hr />
          <div className="text-muted">Listado pendiente (FE-02).</div>
        </Card.Body>
      </Card>
    </div>
  );
}
