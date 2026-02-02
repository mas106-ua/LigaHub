import { useEffect, useState } from "react";
import { Alert, Badge, Button, Card, Spinner } from "react-bootstrap";
import { Link, useNavigate, useParams } from "react-router-dom";
import { getPrivateLeagueDetail } from "../api/privateLeagueDetail";

export default function MyLeagueDetailPage() {
  const { leagueId } = useParams();
  const navigate = useNavigate();

  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const load = async () => {
    setLoading(true);
    setError("");
    try {
      const res = await getPrivateLeagueDetail(leagueId);
      setData(res);
    } catch (e) {
      const status = e?.response?.status;
      if (status === 404) setError("No tienes acceso a esta liga (o no existe).");
      else if (status === 401) setError("Necesitas iniciar sesión.");
      else setError("No se pudo cargar la liga.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leagueId]);

  if (loading) {
    return (
      <div className="d-flex justify-content-center">
        <Card className="shadow-sm border-0" style={{ maxWidth: 720, width: "100%", borderRadius: "1rem" }}>
          <Card.Body className="p-4 d-flex align-items-center gap-2">
            <Spinner animation="border" size="sm" />
            <span>Cargando liga…</span>
          </Card.Body>
        </Card>
      </div>
    );
  }

  if (error) {
    return (
      <div className="d-flex justify-content-center">
        <Card className="shadow-sm border-0" style={{ maxWidth: 720, width: "100%", borderRadius: "1rem" }}>
          <Card.Body className="p-4">
            <Alert variant="danger" className="mb-3">{error}</Alert>
            <Button variant="secondary" onClick={() => navigate("/mis-ligas")}>
              Volver
            </Button>
          </Card.Body>
        </Card>
      </div>
    );
  }

  const role = data?.permissions?.role_in_league || "member";
  const canManage = !!data?.permissions?.can_manage;

  return (
    <div className="d-flex justify-content-center">
      <Card className="shadow-sm border-0" style={{ maxWidth: 900, width: "100%", borderRadius: "1rem" }}>
        <div
          style={{
            background: "linear-gradient(135deg, #C8102E 0%, #990021 100%)",
            color: "#fff",
            borderTopLeftRadius: "1rem",
            borderTopRightRadius: "1rem",
            padding: "1.25rem 1.25rem 1rem",
          }}
        >
          <div className="d-flex justify-content-between align-items-start gap-3">
            <div>
              <h4 className="mb-1 fw-semibold">{data?.name}</h4>
              <div className="text-white-50" style={{ fontSize: "0.95rem" }}>
                Liga privada · {data?.season?.code ? `Temporada ${data.season.code}` : "Sin temporada"}
              </div>
            </div>

            <div className="d-flex flex-column align-items-end gap-2">
              <Badge bg={canManage ? "light" : "secondary"} text={canManage ? "dark" : undefined}>
                {canManage ? "Admin liga" : `Rol: ${role}`}
              </Badge>

              <Button size="sm" variant="light" onClick={() => navigate("/mis-ligas")}>
                Volver
              </Button>
            </div>
          </div>
        </div>

        <Card.Body className="p-4">
          <div className="d-flex flex-wrap gap-2 mb-3">
            <Badge bg="secondary">
              Equipos: {Array.isArray(data?.teams) ? data.teams.length : 0}
            </Badge>
            <Badge bg="secondary">
              Jornadas: {data?.matchdays?.min && data?.matchdays?.max ? `${data.matchdays.min}–${data.matchdays.max}` : "—"}
            </Badge>
            <Badge bg="secondary">
              Partidos: {data?.matchdays?.total_matches ?? 0}
            </Badge>
            <Badge bg="secondary">
              Jugados: {data?.matchdays?.played_matches ?? 0}
            </Badge>
          </div>

          <div className="mb-3 text-muted" style={{ fontSize: "0.95rem" }}>
            {data?.category?.name ? `Categoría: ${data.category.name}` : "Categoría: —"}
            {data?.region?.name ? ` · Región: ${data.region.name}` : ""}
          </div>

          <div className="d-flex gap-2 flex-wrap">
            {canManage && (
              <Button as={Link} to={`/mis-ligas/${leagueId}/calendario`} variant="success">
                Calendario
              </Button>
            )}

            <Button as={Link} to={`/mis-ligas/${leagueId}/equipos`} variant="primary">
              Equipos
            </Button>

            <Button as={Link} to={`/mis-ligas/${leagueId}/jugadores`} variant="outline-primary">
              Jugadores
            </Button>

            <Button as={Link} to={`/mis-ligas/${leagueId}/jornadas`} variant="outline-primary">
              Jornadas
            </Button>

            <Button as={Link} to={`/mis-ligas/${leagueId}/estadisticas`} variant="outline-secondary" disabled>
              Estadísticas (próx.)
            </Button>
          </div>

          <hr />

          <div className="text-muted">
            Resumen:
            <ul className="mb-0 mt-2">
              <li>Grupos: {Array.isArray(data?.groups) ? data.groups.length : 0}</li>
              <li>Clasificación: {data?.features?.has_standings ? "Disponible" : "—"}</li>
              <li>Stats: {data?.features?.has_stats ? "Disponible" : "—"}</li>
            </ul>
          </div>
        </Card.Body>
      </Card>
    </div>
  );
}
