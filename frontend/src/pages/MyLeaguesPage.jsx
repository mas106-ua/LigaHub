import { useEffect, useState } from "react";
import { Alert, Card, Button, Spinner } from "react-bootstrap";
import { Link, useLocation } from "react-router-dom";
import { getMyPrivateLeagues } from "../api/myLeagues";

export default function MyLeaguesPage() {
  const location = useLocation();
  const flash = location.state?.flash;

  const [leagues, setLeagues] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const load = async () => {
    setLoading(true);
    setError("");
    try {
      const items = await getMyPrivateLeagues();
      setLeagues(items);
    } catch (e) {
      const status = e?.response?.status;
      if (status === 401) setError("Necesitas iniciar sesión para ver tus ligas.");
      else setError("No se pudieron cargar tus ligas.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="d-flex justify-content-center">
      <Card className="shadow-sm border-0" style={{ maxWidth: 720, width: "100%", borderRadius: "1rem" }}>
        <Card.Body className="p-4">
          {flash && <Alert variant="success">{flash}</Alert>}
          {error && <Alert variant="danger">{error}</Alert>}

          <div className="d-flex align-items-center justify-content-between gap-3">
            <div>
              <h4 className="mb-1">Mis ligas</h4>
              <div className="text-muted" style={{ fontSize: "0.95rem" }}>
                Aquí verás tus ligas privadas.
              </div>
            </div>

            <div className="d-flex gap-2">
              <Button variant="outline-secondary" onClick={load} disabled={loading}>
                {loading ? (
                  <>
                    <Spinner animation="border" size="sm" /> Actualizando...
                  </>
                ) : (
                  "Actualizar"
                )}
              </Button>

              <Button as={Link} to="/mis-ligas/crear" variant="primary">
                Crear liga
              </Button>
            </div>
          </div>

          <hr />

          {loading && (
            <div className="d-flex align-items-center gap-2">
              <Spinner animation="border" size="sm" />
              <span>Cargando ligas…</span>
            </div>
          )}

          {!loading && !error && leagues.length === 0 && (
            <div className="text-muted">Aún no tienes ligas privadas.</div>
          )}

          {!loading && !error && leagues.length > 0 && (
            <div className="list-group">
              {leagues.map((l) => (
                <div
                  key={l.id}
                  className="list-group-item d-flex justify-content-between align-items-center"
                >
                  <div>
                    <div className="fw-semibold">{l.name}</div>
                    <div className="text-muted small">
                      {l.season?.code ? `Temporada: ${l.season.code} · ` : ""}
                      Rol: {l.role_in_league}
                    </div>
                  </div>

                  <Button as={Link} to={`/mis-ligas/${l.id}`} variant="outline-primary" size="sm">
                    Ver
                  </Button>
                </div>
              ))}
            </div>
          )}
        </Card.Body>
      </Card>
    </div>
  );
}
