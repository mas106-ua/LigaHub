import { useEffect, useState } from "react";
import { Alert, Button, Card, Form, Spinner, Table } from "react-bootstrap";
import { useNavigate, useParams } from "react-router-dom";
import { getPrivateLeagueDetail } from "../api/privateLeagueDetail";
import { getPrivateLeagueMatchesByMatchday } from "../api/privateLeagueMatchdays";
import { updatePrivateLeagueResults } from "../api/privateLeagueResults";

const STATUS_OPTIONS = [
  { value: "scheduled", label: "Programado" },
  { value: "played", label: "Jugado" },
  { value: "postponed", label: "Aplazado" },
  { value: "canceled", label: "Cancelado" },
];

export default function MyLeagueMatchdayEditPage() {
  const { leagueId, matchday } = useParams();
  const navigate = useNavigate();

  const [league, setLeague] = useState(null);
  const [matches, setMatches] = useState([]);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leagueId, matchday]);

  const load = async () => {
    setLoading(true);
    setError("");
    setSuccess("");

    try {
      const leagueData = await getPrivateLeagueDetail(leagueId);

      if (!leagueData?.permissions?.can_manage) {
        setError("No tienes permisos para editar resultados.");
        setLeague(leagueData);
        setMatches([]);
        return;
      }

      const data = await getPrivateLeagueMatchesByMatchday(leagueId, matchday);

      setLeague(leagueData);

      // ✅ CLAVE: solo cargar goles si está 'played'
      setMatches(
        (data ?? []).map((m) => {
          const isPlayed = m.status === "played";

          return {
            id: m.id,
            home_team: m.home_team,
            away_team: m.away_team,
            home_goals: isPlayed ? (m.score?.home ?? "") : "",
            away_goals: isPlayed ? (m.score?.away ?? "") : "",
            status: m.status,
          };
        })
      );
    } catch {
      setError("No se pudieron cargar los partidos de la jornada.");
    } finally {
      setLoading(false);
    }
  };

  const updateMatch = (index, field, value) => {
    setMatches((prev) =>
      prev.map((m, i) => (i === index ? { ...m, [field]: value } : m))
    );
  };

  const handleSave = async () => {
    setSaving(true);
    setError("");
    setSuccess("");

    try {
      const payload = matches.map((m) => ({
        id: m.id,
        home_goals: m.home_goals === "" ? null : Number(m.home_goals),
        away_goals: m.away_goals === "" ? null : Number(m.away_goals),
        status: m.status,
      }));

      await updatePrivateLeagueResults(leagueId, matchday, payload);

      setSuccess("Resultados guardados correctamente.");
      navigate(`/mis-ligas/${leagueId}/jornadas`);
    } catch (e) {
      const status = e?.response?.status;
      if (status === 403) {
        setError("No tienes permisos para guardar resultados.");
      } else if (status === 422) {
        setError("Hay errores en los datos. Revisa goles y estados.");
      } else {
        setError("No se pudieron guardar los resultados.");
      }
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="d-flex justify-content-center">
        <Spinner animation="border" />
      </div>
    );
  }

  return (
    <div className="d-flex justify-content-center">
      <Card className="shadow-sm border-0" style={{ maxWidth: 1000, width: "100%" }}>
        <Card.Body className="p-4">
          <div className="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 className="mb-1">Editar resultados · Jornada {matchday}</h4>
              <div className="text-muted">{league?.name}</div>
            </div>

            <Button
              variant="secondary"
              onClick={() => navigate(`/mis-ligas/${leagueId}/jornadas`)}
            >
              Volver
            </Button>
          </div>

          {error && <Alert variant="danger">{error}</Alert>}
          {success && <Alert variant="success">{success}</Alert>}

          <Table bordered hover responsive className="align-middle">
            <thead>
              <tr>
                <th>Local</th>
                <th className="text-center">Goles</th>
                <th>Visitante</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              {matches.map((m, idx) => (
                <tr key={m.id}>
                  <td>{m.home_team?.name}</td>

                  <td className="text-center" style={{ width: 180 }}>
                    <Form.Control
                      type="number"
                      min={0}
                      step={1}
                      inputMode="numeric"
                      value={m.home_goals}
                      onChange={(e) => updateMatch(idx, "home_goals", e.target.value)}
                      style={{ width: 70, display: "inline-block" }}
                      className="me-1"
                      disabled={m.status !== "played"}   // opcional pero recomendado
                    />

                    -

                    <Form.Control
                      type="number"
                      min={0}
                      step={1}
                      inputMode="numeric"
                      value={m.away_goals}
                      onChange={(e) => updateMatch(idx, "away_goals", e.target.value)}
                      style={{ width: 70, display: "inline-block" }}
                      className="ms-1"
                      disabled={m.status !== "played"}   // opcional pero recomendado
                    />
                  </td>

                  <td>{m.away_team?.name}</td>

                  <td style={{ width: 220 }}>
                    <Form.Select
                      value={m.status}
                      onChange={(e) => {
                        const newStatus = e.target.value;

                        updateMatch(idx, "status", newStatus);

                        if (newStatus !== "played") {
                          updateMatch(idx, "home_goals", "");
                          updateMatch(idx, "away_goals", "");
                        }
                      }}
                    >
                      {STATUS_OPTIONS.map((o) => (
                        <option key={o.value} value={o.value}>
                          {o.label}
                        </option>
                      ))}
                    </Form.Select>
                  </td>
                </tr>
              ))}
            </tbody>
          </Table>

          <div className="d-flex justify-content-end">
            <Button variant="success" onClick={handleSave} disabled={saving}>
              {saving ? "Guardando…" : "Guardar resultados"}
            </Button>
          </div>
        </Card.Body>
      </Card>
    </div>
  );
}
