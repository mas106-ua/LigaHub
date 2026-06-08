import { useEffect, useState } from "react";
import {
  Alert,
  Badge,
  Button,
  Card,
  Form,
  Spinner,
  Table,
} from "react-bootstrap";
import { useNavigate, useParams } from "react-router-dom";
import { getPrivateLeagueDetail } from "../api/privateLeagueDetail";
import { getPrivateLeagueStats } from "../api/privateLeagueStats";

export default function MyLeagueStatsPage() {
  const { leagueId } = useParams();
  const navigate = useNavigate();

  const [league, setLeague] = useState(null);
  const [stats, setStats] = useState(null);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Filtros
  const [lastN, setLastN] = useState(5);
  const [fromMatchday, setFromMatchday] = useState("");
  const [toMatchday, setToMatchday] = useState("");

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leagueId]);

  const load = async (override = {}) => {
    setLoading(true);
    setError("");

    try {
      const leagueData = await getPrivateLeagueDetail(leagueId);

      const params = {
        last_n: lastN,
        ...(fromMatchday && { from_matchday: fromMatchday }),
        ...(toMatchday && { to_matchday: toMatchday }),
        ...override,
      };

      const statsData = await getPrivateLeagueStats(leagueId, params);

      setLeague(leagueData);
      setStats(statsData);
    } catch (e) {
      setError("No se pudieron cargar las estadísticas.");
    } finally {
      setLoading(false);
    }
  };

  const applyFilters = () => load();

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
      <Card className="shadow-sm border-0" style={{ maxWidth: 1100, width: "100%" }}>
        <Card.Body className="p-4">
          {/* Header */}
          <div className="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 className="mb-1">Estadísticas · {league?.name}</h4>
              <div className="text-muted">Análisis avanzado de la liga</div>
            </div>

            <div className="d-flex gap-2">
              <Button variant="outline-secondary" onClick={applyFilters}>
                Aplicar filtros
              </Button>
              <Button
                variant="secondary"
                onClick={() => navigate(`/mis-ligas/${leagueId}`)}
              >
                Volver
              </Button>
            </div>
          </div>

          {/* Filtros */}
          <div className="d-flex flex-wrap gap-3 mb-4">
            <Form.Group style={{ maxWidth: 200 }}>
              <Form.Label>Últimos N partidos</Form.Label>
              <Form.Select value={lastN} onChange={(e) => setLastN(e.target.value)}>
                {[3, 5, 7, 10].map((n) => (
                  <option key={n} value={n}>{n}</option>
                ))}
              </Form.Select>
            </Form.Group>

            <Form.Group style={{ maxWidth: 160 }}>
              <Form.Label>Desde jornada</Form.Label>
              <Form.Control
                type="number"
                value={fromMatchday}
                onChange={(e) => setFromMatchday(e.target.value)}
              />
            </Form.Group>

            <Form.Group style={{ maxWidth: 160 }}>
              <Form.Label>Hasta jornada</Form.Label>
              <Form.Control
                type="number"
                value={toMatchday}
                onChange={(e) => setToMatchday(e.target.value)}
              />
            </Form.Group>
          </div>

          {/* Bloques */}
          {stats?.form_ranking?.length > 0 && (
            <StatsBlock title="Mejor forma (últimos partidos)">
              <SimpleTable
                rows={stats.form_ranking}
                columns={[
                  { key: "team.name", label: "Equipo" },
                  { key: "points_last_n", label: "Puntos" },
                  { key: "form", label: "Forma" },
                ]}
                render={{
                  form: (f) =>
                    f.map((r, i) => (
                      <Badge
                        key={i}
                        bg={r === "W" ? "success" : r === "D" ? "secondary" : "danger"}
                        className="me-1"
                      >
                        {r}
                      </Badge>
                    )),
                }}
              />
            </StatsBlock>
          )}

          {stats?.goals_per_match?.length > 0 && (
            <StatsBlock title="Goles por partido">
              <SimpleTable
                rows={stats.goals_per_match}
                columns={[
                  { key: "team.name", label: "Equipo" },
                  { key: "value", label: "Goles / partido" },
                ]}
              />
            </StatsBlock>
          )}

          {stats?.clean_sheets_ranking?.length > 0 && (
            <StatsBlock title="Porterías a cero">
              <SimpleTable
                rows={stats.clean_sheets_ranking}
                columns={[
                  { key: "team.name", label: "Equipo" },
                  { key: "value", label: "Porterías a cero" },
                ]}
              />
            </StatsBlock>
          )}

          {stats?.most_scoring_teams?.length > 0 && (
            <StatsBlock title="Equipos más goleadores">
              <SimpleTable
                rows={stats.most_scoring_teams}
                columns={[
                  { key: "team.name", label: "Equipo" },
                  { key: "value", label: "Goles a favor" },
                ]}
              />
            </StatsBlock>
          )}

          {stats?.most_conceding_teams?.length > 0 && (
            <StatsBlock title="Equipos más goleados">
              <SimpleTable
                rows={stats.most_conceding_teams}
                columns={[
                  { key: "team.name", label: "Equipo" },
                  { key: "value", label: "Goles encajados" },
                ]}
              />
            </StatsBlock>
          )}

          {stats?.top_scorers?.length > 0 && (
            <StatsBlock title="Máximos goleadores">
              {/* preparado para futuro */}
            </StatsBlock>
          )}
        </Card.Body>
      </Card>
    </div>
  );
}

/* ---------- Helpers UI ---------- */

function StatsBlock({ title, children }) {
  return (
    <div className="mb-4">
      <h5 className="mb-2">{title}</h5>
      {children}
    </div>
  );
}

function SimpleTable({ rows, columns, render = {} }) {
  return (
    <Table bordered hover responsive className="align-middle">
      <thead>
        <tr>
          {columns.map((c) => (
            <th key={c.key}>{c.label}</th>
          ))}
        </tr>
      </thead>
      <tbody>
        {rows.map((r, i) => (
          <tr key={i}>
            {columns.map((c) => {
              const value = c.key.split(".").reduce((o, k) => o?.[k], r);
              return (
                <td key={c.key}>
                  {render[c.key] ? render[c.key](value, r) : value}
                </td>
              );
            })}
          </tr>
        ))}
      </tbody>
    </Table>
  );
}
