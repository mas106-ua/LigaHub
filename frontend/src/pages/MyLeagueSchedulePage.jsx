import { useEffect, useMemo, useState } from "react";
import {
  Alert,
  Badge,
  Button,
  Card,
  Form,
  Modal,
  Spinner,
  Accordion,
  Table,
} from "react-bootstrap";
import { useNavigate, useParams } from "react-router-dom";
import { getPrivateLeagueDetail } from "../api/privateLeagueDetail";
import {
  previewPrivateLeagueSchedule,
  publishPrivateLeagueSchedule,
} from "../api/privateLeagueSchedule";
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
  scheduled: "Programado",
  played: "Jugado",
  postponed: "Aplazado",
  canceled: "Cancelado",
};

function matchScore(match) {
  if (match.status !== "played") return "—";

  const home = match.score?.home;
  const away = match.score?.away;

  if (home === null || home === undefined || away === null || away === undefined) {
    return "—";
  }

  return `${home} - ${away}`;
}

export default function MyLeagueSchedulePage() {
  const { leagueId } = useParams();
  const navigate = useNavigate();

  const [league, setLeague] = useState(null);
  const [loadingLeague, setLoadingLeague] = useState(true);
  const [errorLeague, setErrorLeague] = useState("");

  const [type, setType] = useState("single");
  const [preview, setPreview] = useState(null);
  const [loadingPreview, setLoadingPreview] = useState(false);
  const [errorPreview, setErrorPreview] = useState("");

  const [publishedSchedule, setPublishedSchedule] = useState([]);
  const [loadingPublishedSchedule, setLoadingPublishedSchedule] = useState(false);
  const [publishedScheduleError, setPublishedScheduleError] = useState("");

  const [showConfirm, setShowConfirm] = useState(false);
  const [publishing, setPublishing] = useState(false);
  const [publishError, setPublishError] = useState("");
  const [publishOk, setPublishOk] = useState("");

  const canManage = !!league?.permissions?.can_manage;
  const role = league?.permissions?.role_in_league || "member";

  const hasPublishedSchedule =
    Array.isArray(publishedSchedule) && publishedSchedule.length > 0;

  const titleType = useMemo(
    () => (type === "double" ? "Ida y vuelta" : "Solo ida"),
    [type]
  );

  const loadPublishedSchedule = async () => {
    setLoadingPublishedSchedule(true);
    setPublishedScheduleError("");

    try {
      const days = await getPrivateLeagueMatchdays(leagueId);

      if (!Array.isArray(days) || days.length === 0) {
        setPublishedSchedule([]);
        return;
      }

      const daysWithMatches = await Promise.all(
        days.map(async (day) => {
          const matches = await getPrivateLeagueMatchesByMatchday(
            leagueId,
            day.number
          );

          return {
            ...day,
            matches: Array.isArray(matches) ? matches : [],
          };
        })
      );

      setPublishedSchedule(daysWithMatches);
    } catch {
      setPublishedSchedule([]);
      setPublishedScheduleError("No se pudo cargar el calendario publicado.");
    } finally {
      setLoadingPublishedSchedule(false);
    }
  };

  const loadLeague = async () => {
    setLoadingLeague(true);
    setErrorLeague("");
    setPublishedSchedule([]);
    setPublishedScheduleError("");

    try {
      const data = await getPrivateLeagueDetail(leagueId);
      setLeague(data);

      const hasMatchdays =
        data?.features?.has_matchdays ||
        Number(data?.matchdays?.total_matches ?? 0) > 0;

      if (hasMatchdays) {
        await loadPublishedSchedule();
      }
    } catch (e) {
      const status = e?.response?.status;

      if (status === 404) {
        setErrorLeague("No tienes acceso a esta liga (o no existe).");
      } else if (status === 401) {
        setErrorLeague("Necesitas iniciar sesión.");
      } else {
        setErrorLeague("No se pudo cargar la liga.");
      }
    } finally {
      setLoadingLeague(false);
    }
  };

  useEffect(() => {
    loadLeague();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leagueId]);

  const handlePreview = async () => {
    setPublishOk("");
    setPublishError("");
    setLoadingPreview(true);
    setErrorPreview("");
    setPreview(null);

    try {
      const data = await previewPrivateLeagueSchedule(leagueId, type);
      setPreview(data);
    } catch (e) {
      const status = e?.response?.status;

      if (status === 403) {
        setErrorPreview(
          "No tienes permisos para generar el calendario (solo admin/owner)."
        );
      } else if (status === 404) {
        setErrorPreview("No tienes acceso a esta liga (o no existe).");
      } else if (status === 422) {
        setErrorPreview("No se pudo generar: revisa que haya equipos suficientes.");
      } else if (status === 409) {
        setErrorPreview("El calendario ya está publicado y no se puede regenerar.");
      } else {
        setErrorPreview("No se pudo generar el preview del calendario.");
      }
    } finally {
      setLoadingPreview(false);
    }
  };

  const handlePublish = async () => {
    setPublishing(true);
    setPublishError("");
    setPublishOk("");

    try {
      await publishPrivateLeagueSchedule(leagueId, type);

      setPublishOk("Calendario publicado correctamente.");
      setShowConfirm(false);
      setPreview(null);

      await loadLeague();
    } catch (e) {
      const status = e?.response?.status;

      if (status === 409) {
        setPublishError("El calendario ya está publicado y no se puede regenerar.");
      } else if (status === 403) {
        setPublishError("No tienes permisos para publicar el calendario.");
      } else if (status === 422) {
        setPublishError("No se pudo publicar: revisa que haya equipos suficientes.");
      } else {
        setPublishError("No se pudo publicar el calendario.");
      }
    } finally {
      setPublishing(false);
    }
  };

  if (loadingLeague) {
    return (
      <div className="d-flex justify-content-center">
        <Card
          className="shadow-sm border-0"
          style={{ maxWidth: 900, width: "100%", borderRadius: "1rem" }}
        >
          <Card.Body className="p-4 d-flex align-items-center gap-2">
            <Spinner animation="border" size="sm" />
            <span>Cargando…</span>
          </Card.Body>
        </Card>
      </div>
    );
  }

  if (errorLeague) {
    return (
      <div className="d-flex justify-content-center">
        <Card
          className="shadow-sm border-0"
          style={{ maxWidth: 900, width: "100%", borderRadius: "1rem" }}
        >
          <Card.Body className="p-4">
            <Alert variant="danger" className="mb-3">
              {errorLeague}
            </Alert>
            <Button variant="secondary" onClick={() => navigate("/mis-ligas")}>
              Volver
            </Button>
          </Card.Body>
        </Card>
      </div>
    );
  }

  if (!canManage) {
    return (
      <div className="d-flex justify-content-center">
        <Card
          className="shadow-sm border-0"
          style={{ maxWidth: 900, width: "100%", borderRadius: "1rem" }}
        >
          <Card.Body className="p-4">
            <Alert variant="warning" className="mb-3">
              No tienes permisos para generar/publicar el calendario.
              <br />
              Tu rol: <strong>{role}</strong>
            </Alert>
            <Button
              variant="secondary"
              onClick={() => navigate(`/mis-ligas/${leagueId}`)}
            >
              Volver a la liga
            </Button>
          </Card.Body>
        </Card>
      </div>
    );
  }

  return (
    <div className="d-flex justify-content-center">
      <Card
        className="shadow-sm border-0"
        style={{ maxWidth: 1000, width: "100%", borderRadius: "1rem" }}
      >
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
              <h4 className="mb-1 fw-semibold">Calendario · {league?.name}</h4>
              <div className="text-white-50" style={{ fontSize: "0.95rem" }}>
                {hasPublishedSchedule
                  ? "Calendario publicado de la liga"
                  : "Generar preview y publicar calendario"}
              </div>
            </div>

            <div className="d-flex flex-column align-items-end gap-2">
              <Badge bg="light" text="dark">
                Admin/Owner
              </Badge>
              <Button
                size="sm"
                variant="light"
                onClick={() => navigate(`/mis-ligas/${leagueId}`)}
              >
                Volver
              </Button>
            </div>
          </div>
        </div>

        <Card.Body className="p-4">
          {publishOk && <Alert variant="success">{publishOk}</Alert>}
          {publishError && <Alert variant="danger">{publishError}</Alert>}
          {publishedScheduleError && (
            <Alert variant="danger">{publishedScheduleError}</Alert>
          )}

          <div className="d-flex flex-wrap gap-2 mb-3">
            <Badge bg="secondary">
              Equipos: {Array.isArray(league?.teams) ? league.teams.length : 0}
            </Badge>
            <Badge bg={hasPublishedSchedule ? "success" : "secondary"}>
              {hasPublishedSchedule ? "Publicado" : "Borrador"}
            </Badge>
            {hasPublishedSchedule && (
              <Badge bg="secondary">
                Jornadas: {publishedSchedule.length}
              </Badge>
            )}
          </div>

          {loadingPublishedSchedule && (
            <div className="d-flex align-items-center gap-2">
              <Spinner animation="border" size="sm" />
              <span>Cargando calendario publicado…</span>
            </div>
          )}

          {!loadingPublishedSchedule && hasPublishedSchedule && (
            <>
              <Alert variant="info">
                El calendario ya está publicado. No se puede regenerar, pero
                puedes consultarlo aquí o ir a la vista de jornadas para editar
                resultados.
              </Alert>

              <div className="d-flex flex-wrap gap-2 mb-3">
                <Button
                  variant="primary"
                  onClick={() => navigate(`/mis-ligas/${leagueId}/jornadas`)}
                >
                  Ver jornadas
                </Button>

                <Button
                  variant="outline-secondary"
                  onClick={loadPublishedSchedule}
                >
                  Actualizar calendario
                </Button>
              </div>

              <Accordion alwaysOpen>
                {publishedSchedule.map((md) => (
                  <Accordion.Item eventKey={String(md.number)} key={md.number}>
                    <Accordion.Header>
                      Jornada {md.number} · {md.matches_count ?? md.matches.length} partidos
                    </Accordion.Header>

                    <Accordion.Body>
                      <Table responsive bordered hover className="mb-0 align-middle">
                        <thead>
                          <tr>
                            <th style={{ width: "32%" }}>Local</th>
                            <th style={{ width: "16%" }} className="text-center">
                              Resultado
                            </th>
                            <th style={{ width: "32%" }}>Visitante</th>
                            <th style={{ width: "20%" }} className="text-center">
                              Estado
                            </th>
                          </tr>
                        </thead>

                        <tbody>
                          {md.matches.map((match) => (
                            <tr key={match.id}>
                              <td>{match.home_team?.name || "—"}</td>
                              <td className="text-center fw-semibold">
                                {matchScore(match)}
                              </td>
                              <td>{match.away_team?.name || "—"}</td>
                              <td className="text-center">
                                <Badge bg={STATUS_VARIANT[match.status] ?? "secondary"}>
                                  {STATUS_LABEL[match.status] ?? match.status}
                                </Badge>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </Table>
                    </Accordion.Body>
                  </Accordion.Item>
                ))}
              </Accordion>
            </>
          )}

          {!loadingPublishedSchedule && !hasPublishedSchedule && (
            <>
              <Form className="d-flex flex-wrap gap-3 align-items-end">
                <Form.Group style={{ minWidth: 220 }}>
                  <Form.Label className="fw-semibold">Tipo de liga</Form.Label>
                  <Form.Select
                    value={type}
                    onChange={(e) => setType(e.target.value)}
                  >
                    <option value="single">Solo ida</option>
                    <option value="double">Ida y vuelta</option>
                  </Form.Select>
                </Form.Group>

                <div className="d-flex gap-2">
                  <Button
                    variant="primary"
                    onClick={handlePreview}
                    disabled={loadingPreview}
                  >
                    {loadingPreview ? (
                      <>
                        <Spinner animation="border" size="sm" className="me-2" />
                        Generando…
                      </>
                    ) : (
                      "Previsualizar"
                    )}
                  </Button>

                  <Button
                    variant="success"
                    onClick={() => setShowConfirm(true)}
                    disabled={!preview || (preview?.matchdays?.length ?? 0) === 0}
                    title={!preview ? "Genera primero un preview" : ""}
                  >
                    Publicar
                  </Button>
                </div>
              </Form>

              {errorPreview && (
                <Alert variant="danger" className="mt-3">
                  {errorPreview}
                </Alert>
              )}

              <hr className="my-4" />

              {!preview && (
                <div className="text-muted">
                  Genera un preview para ver las jornadas y partidos antes de publicar.
                </div>
              )}

              {preview && (
                <>
                  <div className="d-flex justify-content-between align-items-center mb-2">
                    <h5 className="mb-0">Preview</h5>
                    <span className="text-muted" style={{ fontSize: "0.95rem" }}>
                      Jornadas: {preview?.matchdays?.length ?? 0}
                    </span>
                  </div>

                  <Accordion alwaysOpen>
                    {preview.matchdays.map((md) => (
                      <Accordion.Item eventKey={String(md.number)} key={md.number}>
                        <Accordion.Header>Jornada {md.number}</Accordion.Header>
                        <Accordion.Body>
                          <Table responsive bordered hover className="mb-0 align-middle">
                            <thead>
                              <tr>
                                <th style={{ width: "45%" }}>Local</th>
                                <th style={{ width: "10%" }} className="text-center">
                                  VS
                                </th>
                                <th style={{ width: "45%" }}>Visitante</th>
                              </tr>
                            </thead>

                            <tbody>
                              {md.matches.map((m, idx) => (
                                <tr key={`${md.number}-${idx}`}>
                                  <td>{m.home_team?.name}</td>
                                  <td className="text-center text-muted">-</td>
                                  <td>{m.away_team?.name}</td>
                                </tr>
                              ))}
                            </tbody>
                          </Table>
                        </Accordion.Body>
                      </Accordion.Item>
                    ))}
                  </Accordion>
                </>
              )}
            </>
          )}
        </Card.Body>
      </Card>

      <Modal show={showConfirm} onHide={() => setShowConfirm(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title>Publicar calendario</Modal.Title>
        </Modal.Header>

        <Modal.Body>
          <p className="mb-2">
            Vas a publicar el calendario en formato{" "}
            <strong>{titleType}</strong>.
          </p>
          <p className="mb-0 text-muted">
            Una vez publicado, no se podrá regenerar.
          </p>
        </Modal.Body>

        <Modal.Footer>
          <Button
            variant="secondary"
            onClick={() => setShowConfirm(false)}
            disabled={publishing}
          >
            Cancelar
          </Button>

          <Button variant="success" onClick={handlePublish} disabled={publishing}>
            {publishing ? (
              <>
                <Spinner animation="border" size="sm" className="me-2" />
                Publicando…
              </>
            ) : (
              "Confirmar y publicar"
            )}
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}