import { useEffect, useState } from "react";
import { Alert, Badge, Button, Card, Form, Spinner, Table } from "react-bootstrap";
import { useNavigate, useParams } from "react-router-dom";
import { getPrivateLeagueDetail } from "../api/privateLeagueDetail";
import {
  getPrivateLeagueMatchdays,
  getPrivateLeagueMatchesByMatchday,
} from "../api/privateLeagueMatchdays";

const STATUS_VARIANT = {
  scheduled: "secondary",
  played: "success",
  postponed: "warning",
  canceled: "danger",
};

const STATUS_LABEL = {
  scheduled: "scheduled",
  played: "played",
  postponed: "postponed",
  canceled: "canceled",
};

export default function MyLeagueMatchdaysPage() {
  const { leagueId } = useParams();
  const navigate = useNavigate();

  const [league, setLeague] = useState(null);
  const [matchdays, setMatchdays] = useState([]);
  const [selected, setSelected] = useState(null);
  const [matches, setMatches] = useState([]);

  const [loading, setLoading] = useState(true);
  const [loadingMatches, setLoadingMatches] = useState(false);
  const [error, setError] = useState("");

  const canManage = !!league?.permissions?.can_manage;

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leagueId]);

  const load = async () => {
    setLoading(true);
    setError("");

    try {
      const leagueData = await getPrivateLeagueDetail(leagueId);
      const days = await getPrivateLeagueMatchdays(leagueId);

      setLeague(leagueData);
      setMatchdays(days);

      if (days.length > 0) {
        setSelected(days[0].number);
        loadMatches(days[0].number);
      } else {
        setSelected(null);
        setMatches([]);
      }
    } catch (e) {
      setError("No se pudieron cargar las jornadas de la liga.");
    } finally {
      setLoading(false);
    }
  };

  const loadMatches = async (matchday) => {
    setLoadingMatches(true);
    try {
      const data = await getPrivateLeagueMatchesByMatchday(leagueId, matchday);
      setMatches(data ?? []);
    } catch {
      setMatches([]);
    } finally {
      setLoadingMatches(false);
    }
  };

  const onChangeMatchday = (e) => {
    const md = Number(e.target.value);
    setSelected(md);
    loadMatches(md);
  };

  const goEdit = () => {
    if (!selected) return;
    navigate(`/mis-ligas/${leagueId}/jornadas/${selected}/editar`);
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
              <h4 className="mb-1">Jornadas · {league?.name}</h4>
              <div className="text-muted">Calendario y partidos</div>
            </div>

            <div className="d-flex gap-2">
              {canManage && selected && (
                <Button variant="outline-primary" onClick={goEdit}>
                  Editar jornada
                </Button>
              )}
              <Button variant="secondary" onClick={() => navigate(`/mis-ligas/${leagueId}`)}>
                Volver
              </Button>
            </div>
          </div>

          {matchdays.length === 0 && (
            <Alert variant="info">El calendario aún no está publicado.</Alert>
          )}

          {matchdays.length > 0 && (
            <>
              <Form.Group className="mb-3" style={{ maxWidth: 220 }}>
                <Form.Label className="fw-semibold">Jornada</Form.Label>
                <Form.Select value={selected ?? ""} onChange={onChangeMatchday}>
                  {matchdays.map((md) => (
                    <option key={md.number} value={md.number}>
                      Jornada {md.number}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>

              {loadingMatches ? (
                <Spinner animation="border" />
              ) : (
                <Table bordered hover responsive className="align-middle">
                  <thead>
                    <tr>
                      <th>Local</th>
                      <th className="text-center">Resultado</th>
                      <th>Visitante</th>
                      <th className="text-center">Estado</th>
                    </tr>
                  </thead>
                  <tbody>
                    {matches.map((m) => (
                      <tr key={m.id}>
                        <td>{m.home_team?.name}</td>
                        <td className="text-center">
                          {m.status === "played"
                            ? `${m.score?.home ?? 0} - ${m.score?.away ?? 0}`
                            : "—"}
                        </td>
                        <td>{m.away_team?.name}</td>
                        <td className="text-center">
                          <Badge bg={STATUS_VARIANT[m.status] ?? "secondary"}>
                            {STATUS_LABEL[m.status] ?? m.status}
                          </Badge>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
              )}
            </>
          )}
        </Card.Body>
      </Card>
    </div>
  );
}
