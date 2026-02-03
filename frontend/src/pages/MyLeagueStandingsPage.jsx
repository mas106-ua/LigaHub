import { useEffect, useState } from "react";
import { Alert, Badge, Button, Card, Form, Spinner, Table } from "react-bootstrap";
import { useNavigate, useParams } from "react-router-dom";
import { getPrivateLeagueDetail } from "../api/privateLeagueDetail";
import { getPrivateLeagueStandings } from "../api/privateLeagueStandings";

export default function MyLeagueStandingsPage() {
  const { leagueId } = useParams();
  const navigate = useNavigate();

  const [league, setLeague] = useState(null);
  const [rows, setRows] = useState([]);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Selector simple (para UX): mostrar/ocultar columnas “extra”
  const [view, setView] = useState("compact"); // compact | full

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leagueId]);

  const load = async () => {
    setLoading(true);
    setError("");

    try {
      const leagueData = await getPrivateLeagueDetail(leagueId);
      const data = await getPrivateLeagueStandings(leagueId);

      setLeague(leagueData);
      setRows(data);
    } catch (e) {
      const status = e?.response?.status;
      if (status === 404) setError("No tienes acceso a esta liga (o no existe).");
      else setError("No se pudo cargar la clasificación.");
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="d-flex justify-content-center">
        <Spinner animation="border" />
      </div>
    );
  }

  if (error) {
    return <Alert variant="danger">{error}</Alert>;
  }

  return (
    <div className="d-flex justify-content-center">
      <Card className="shadow-sm border-0" style={{ maxWidth: 1000, width: "100%" }}>
        <Card.Body className="p-4">
          <div className="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 className="mb-1">Clasificación · {league?.name}</h4>
              <div className="text-muted">Tabla de posiciones</div>
            </div>

            <div className="d-flex gap-2">
              <Button variant="outline-secondary" onClick={load}>
                Refrescar
              </Button>
              <Button variant="secondary" onClick={() => navigate(`/mis-ligas/${leagueId}`)}>
                Volver
              </Button>
            </div>
          </div>

          <div className="d-flex flex-wrap align-items-end gap-3 mb-3">
            <Form.Group style={{ maxWidth: 220 }}>
              <Form.Label className="fw-semibold">Vista</Form.Label>
              <Form.Select value={view} onChange={(e) => setView(e.target.value)}>
                <option value="compact">Compacta</option>
                <option value="full">Completa</option>
              </Form.Select>
            </Form.Group>

            <div className="text-muted" style={{ fontSize: "0.95rem" }}>
              Se actualiza al guardar resultados (puedes refrescar para asegurar).
            </div>
          </div>

          {rows.length === 0 ? (
            <Alert variant="info">Aún no hay datos (no hay partidos jugados).</Alert>
          ) : (
            <Table bordered hover responsive className="align-middle">
              <thead>
                <tr>
                  <th style={{ width: 60 }} className="text-center">Pos</th>
                  <th>Equipo</th>
                  <th className="text-center" style={{ width: 70 }}>PJ</th>
                  <th className="text-center" style={{ width: 70 }}>Pts</th>
                  {view === "full" && (
                    <>
                      <th className="text-center" style={{ width: 70 }}>G</th>
                      <th className="text-center" style={{ width: 70 }}>E</th>
                      <th className="text-center" style={{ width: 70 }}>P</th>
                      <th className="text-center" style={{ width: 70 }}>GF</th>
                      <th className="text-center" style={{ width: 70 }}>GC</th>
                      <th className="text-center" style={{ width: 70 }}>DG</th>
                    </>
                  )}
                </tr>
              </thead>
              <tbody>
                {rows.map((r) => (
                  <tr key={r.team?.id}>
                    <td className="text-center">
                      <Badge bg="light" text="dark">{r.position}</Badge>
                    </td>
                    <td>{r.team?.name}</td>
                    <td className="text-center">{r.played}</td>
                    <td className="text-center fw-semibold">{r.points}</td>

                    {view === "full" && (
                      <>
                        <td className="text-center">{r.wins}</td>
                        <td className="text-center">{r.draws}</td>
                        <td className="text-center">{r.losses}</td>
                        <td className="text-center">{r.gf}</td>
                        <td className="text-center">{r.ga}</td>
                        <td className="text-center">{r.gd}</td>
                      </>
                    )}
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </Card.Body>
      </Card>
    </div>
  );
}
