import { useEffect, useMemo, useState } from "react";
import { Alert, Badge, Button, Card, Form, Modal, Spinner, Table } from "react-bootstrap";
import { Link, useParams } from "react-router-dom";
import { getPrivateLeagueTeams } from "../api/privateLeagueTeams";
import {
  createPrivateLeaguePlayer,
  deletePrivateLeaguePlayer,
  getPrivateLeaguePlayers,
  updatePrivateLeaguePlayer,
} from "../api/privateLeaguePlayers";

function firstErr(errors, key) {
  const v = errors?.[key];
  if (!v) return "";
  if (Array.isArray(v)) return v[0] || "";
  if (typeof v === "string") return v;
  return "";
}

const POSITIONS = [
  { value: "NA", label: "—" },
  { value: "GK", label: "Portero (GK)" },
  { value: "DF", label: "Defensa (DF)" },
  { value: "MF", label: "Mediocentro (MF)" },
  { value: "FW", label: "Delantero (FW)" },
];

export default function MyLeaguePlayersPage() {
  const { leagueId } = useParams();

  const [teams, setTeams] = useState([]);
  const [teamId, setTeamId] = useState(""); // filtro
  const [players, setPlayers] = useState([]);

  const [loadingTeams, setLoadingTeams] = useState(true);
  const [loadingPlayers, setLoadingPlayers] = useState(true);

  const [busy, setBusy] = useState(false);
  const [flash, setFlash] = useState("");
  const [error, setError] = useState("");

  // modal
  const [showModal, setShowModal] = useState(false);
  const [mode, setMode] = useState("create"); // create | edit
  const [current, setCurrent] = useState(null);
  const [form, setForm] = useState({
    full_name: "",
    team_id: "",
    position: "NA",
    shirt_number: "",
  });
  const [formErrors, setFormErrors] = useState({});

  const modalTitle = useMemo(() => (mode === "create" ? "Nuevo jugador" : "Editar jugador"), [mode]);

  const loadTeams = async () => {
    setLoadingTeams(true);
    setError("");
    try {
      const t = await getPrivateLeagueTeams(leagueId);
      setTeams(t);

      // default: primer equipo si existe
      if (t.length > 0) {
        setTeamId(String(t[0].id));
      } else {
        setTeamId("");
      }
    } catch (e) {
      const status = e?.response?.status;
      if (status === 404) setError("No tienes acceso a esta liga (o no existe).");
      else if (status === 401) setError("Necesitas iniciar sesión.");
      else setError("No se pudieron cargar los equipos.");
    } finally {
      setLoadingTeams(false);
    }
  };

  const loadPlayers = async (tid) => {
    setLoadingPlayers(true);
    setError("");
    try {
      const list = await getPrivateLeaguePlayers(leagueId, tid ? Number(tid) : null);
      setPlayers(list);
    } catch (e) {
      const status = e?.response?.status;
      if (status === 404) setError("No tienes acceso a esta liga (o no existe).");
      else if (status === 401) setError("Necesitas iniciar sesión.");
      else if (status === 403) setError("No tienes permisos para ver los jugadores.");
      else setError("No se pudieron cargar los jugadores.");
    } finally {
      setLoadingPlayers(false);
    }
  };

  useEffect(() => {
    loadTeams();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leagueId]);

  useEffect(() => {
    // Cuando cambia el filtro (teamId) refrescamos jugadores
    loadPlayers(teamId);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leagueId, teamId]);

  const openCreate = () => {
    setMode("create");
    setCurrent(null);
    setFlash("");
    setFormErrors({});
    setForm({
      full_name: "",
      team_id: teamId || (teams[0] ? String(teams[0].id) : ""),
      position: "NA",
      shirt_number: "",
    });
    setShowModal(true);
  };

  const openEdit = (p) => {
    setMode("edit");
    setCurrent(p);
    setFlash("");
    setFormErrors({});
    setForm({
      full_name: p.full_name ?? "",
      team_id: p.team_id ? String(p.team_id) : "",
      position: p.position ?? "NA",
      shirt_number: p.shirt_number ?? "",
    });
    setShowModal(true);
  };

  const closeModal = () => {
    if (busy) return;
    setShowModal(false);
  };

  const submit = async (e) => {
    e.preventDefault();
    setFormErrors({});
    setFlash("");
    setError("");

    const fullName = String(form.full_name || "").trim();
    const team = String(form.team_id || "").trim();

    if (!fullName) {
      setFormErrors({ full_name: ["El nombre es obligatorio."] });
      return;
    }
    if (!team) {
      setFormErrors({ team_id: ["Selecciona un equipo."] });
      return;
    }

    const payload = {
      full_name: fullName,
      team_id: Number(team),
      position: form.position || "NA",
      shirt_number: form.shirt_number === "" ? null : Number(form.shirt_number),
    };

    setBusy(true);
    try {
      if (mode === "create") {
        const created = await createPrivateLeaguePlayer(leagueId, payload);
        setFlash("Jugador creado.");
        setShowModal(false);

        // Para que "se refleje al instante": si estás filtrando por otro equipo, te llevo al equipo creado.
        const createdTeamId = String(payload.team_id);
        if (teamId !== createdTeamId) setTeamId(createdTeamId);
        else await loadPlayers(teamId);

        return created;
      } else {
        await updatePrivateLeaguePlayer(leagueId, current.id, payload);
        setFlash("Jugador actualizado.");
        setShowModal(false);

        const updatedTeamId = String(payload.team_id);
        if (teamId !== updatedTeamId) setTeamId(updatedTeamId);
        else await loadPlayers(teamId);
      }
    } catch (e2) {
      if (e2?.response?.status === 422) {
        setFormErrors(e2.response.data?.errors || { general: ["Validación incorrecta."] });
      } else if (e2?.response?.status === 403) {
        setFormErrors({ general: ["No tienes permisos para gestionar jugadores en esta liga."] });
      } else {
        setFormErrors({ general: ["No se pudo guardar el jugador."] });
      }
    } finally {
      setBusy(false);
    }
  };

  const onDelete = async (p) => {
    setFlash("");
    setError("");

    const ok = window.confirm(`¿Eliminar a "${p.full_name}" de la liga?`);
    if (!ok) return;

    setBusy(true);
    try {
      await deletePrivateLeaguePlayer(leagueId, p.id);
      setFlash("Jugador eliminado.");
      await loadPlayers(teamId);
    } catch (e) {
      const status = e?.response?.status;
      if (status === 403) setError("No tienes permisos para eliminar jugadores.");
      else setError("No se pudo eliminar el jugador.");
    } finally {
      setBusy(false);
    }
  };

  const selectedTeamName = useMemo(() => {
    const t = teams.find((x) => String(x.id) === String(teamId));
    return t?.name || "";
  }, [teams, teamId]);

  return (
    <div className="d-flex justify-content-center">
      <Card className="shadow-sm border-0" style={{ maxWidth: 1100, width: "100%", borderRadius: "1rem" }}>
        <Card.Body className="p-4">
          <div className="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
              <h4 className="mb-1">Jugadores</h4>
              <div className="text-muted" style={{ fontSize: "0.95rem" }}>
                Listado por equipo y gestión de plantilla.
              </div>
            </div>

            <div className="d-flex gap-2">
              <Button as={Link} to={`/mis-ligas/${leagueId}`} variant="outline-secondary" disabled={busy}>
                Volver
              </Button>
              <Button variant="primary" onClick={openCreate} disabled={busy || loadingTeams || teams.length === 0}>
                + Nuevo jugador
              </Button>
            </div>
          </div>

          {flash && <Alert className="mt-3" variant="success">{flash}</Alert>}
          {error && <Alert className="mt-3" variant="danger">{error}</Alert>}

          <hr />

          {/* Selector de equipo */}
          <div className="d-flex gap-2 align-items-end flex-wrap">
            <Form.Group style={{ minWidth: 280 }}>
              <Form.Label>Equipo</Form.Label>
              <Form.Select
                value={teamId}
                disabled={loadingTeams || teams.length === 0}
                onChange={(e) => setTeamId(e.target.value)}
              >
                {teams.map((t) => (
                  <option key={t.id} value={t.id}>{t.name}</option>
                ))}
              </Form.Select>
            </Form.Group>

            <div className="text-muted" style={{ paddingBottom: 6 }}>
              {teams.length > 0 && teamId ? <>Mostrando: <strong>{selectedTeamName}</strong></> : null}
            </div>

            <div className="ms-auto d-flex gap-2">
              <Button
                variant="outline-secondary"
                disabled={busy || loadingTeams}
                onClick={() => loadPlayers(teamId)}
              >
                {loadingPlayers ? (
                  <>
                    <Spinner animation="border" size="sm" /> Actualizando…
                  </>
                ) : (
                  "Actualizar"
                )}
              </Button>

              <Button
                as={Link}
                to={`/mis-ligas/${leagueId}/equipos`}
                variant="outline-primary"
                disabled={busy}
              >
                Ir a equipos
              </Button>
            </div>
          </div>

          {loadingTeams && (
            <div className="d-flex align-items-center gap-2 mt-3">
              <Spinner animation="border" size="sm" />
              <span>Cargando equipos…</span>
            </div>
          )}

          {!loadingTeams && teams.length === 0 && !error && (
            <Alert className="mt-3" variant="warning">
              Primero crea equipos para poder añadir jugadores.{" "}
              <Alert.Link as={Link} to={`/mis-ligas/${leagueId}/equipos`}>
                Ir a equipos
              </Alert.Link>
            </Alert>
          )}

          <hr />

          {/* Tabla jugadores */}
          {loadingPlayers && (
            <div className="d-flex align-items-center gap-2">
              <Spinner animation="border" size="sm" />
              <span>Cargando jugadores…</span>
            </div>
          )}

          {!loadingPlayers && !error && teams.length > 0 && players.length === 0 && (
            <div className="text-muted">No hay jugadores en este equipo.</div>
          )}

          {!loadingPlayers && !error && players.length > 0 && (
            <Table responsive hover className="align-middle mt-2">
              <thead>
                <tr>
                  <th>Jugador</th>
                  <th>Posición</th>
                  <th>Dorsal</th>
                  <th className="text-end" style={{ width: 220 }}>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {players.map((p) => (
                  <tr key={p.id}>
                    <td>
                      <div className="fw-semibold">{p.full_name}</div>
                      <div className="text-muted small">
                        {p.team_name ? `Equipo: ${p.team_name}` : ""}
                      </div>
                    </td>
                    <td>
                      <Badge bg="secondary">{p.position || "NA"}</Badge>
                    </td>
                    <td>{p.shirt_number ?? <span className="text-muted">—</span>}</td>
                    <td className="text-end">
                      <Button variant="outline-primary" size="sm" className="me-2" onClick={() => openEdit(p)} disabled={busy}>
                        Editar
                      </Button>
                      <Button variant="outline-danger" size="sm" onClick={() => onDelete(p)} disabled={busy}>
                        Borrar
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </Card.Body>
      </Card>

      {/* Modal create/edit */}
      <Modal show={showModal} onHide={closeModal} centered>
        <Form onSubmit={submit}>
          <Modal.Header closeButton={!busy}>
            <Modal.Title>{modalTitle}</Modal.Title>
          </Modal.Header>

          <Modal.Body>
            {firstErr(formErrors, "general") && <Alert variant="danger">{firstErr(formErrors, "general")}</Alert>}

            <Form.Group className="mb-3">
              <Form.Label>Nombre *</Form.Label>
              <Form.Control
                value={form.full_name}
                onChange={(e) => setForm((s) => ({ ...s, full_name: e.target.value }))}
                disabled={busy}
                isInvalid={!!firstErr(formErrors, "full_name")}
                placeholder="Nombre y apellidos"
              />
              {firstErr(formErrors, "full_name") && (
                <Form.Control.Feedback type="invalid">{firstErr(formErrors, "full_name")}</Form.Control.Feedback>
              )}
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Equipo *</Form.Label>
              <Form.Select
                value={form.team_id}
                onChange={(e) => setForm((s) => ({ ...s, team_id: e.target.value }))}
                disabled={busy || teams.length === 0}
                isInvalid={!!firstErr(formErrors, "team_id")}
              >
                <option value="">Selecciona…</option>
                {teams.map((t) => (
                  <option key={t.id} value={t.id}>{t.name}</option>
                ))}
              </Form.Select>
              {firstErr(formErrors, "team_id") && (
                <div className="invalid-feedback d-block">{firstErr(formErrors, "team_id")}</div>
              )}
            </Form.Group>

            <div className="d-flex gap-3">
              <Form.Group className="mb-3" style={{ flex: 1 }}>
                <Form.Label>Posición</Form.Label>
                <Form.Select
                  value={form.position}
                  onChange={(e) => setForm((s) => ({ ...s, position: e.target.value }))}
                  disabled={busy}
                >
                  {POSITIONS.map((p) => (
                    <option key={p.value} value={p.value}>{p.label}</option>
                  ))}
                </Form.Select>
              </Form.Group>

              <Form.Group className="mb-3" style={{ width: 160 }}>
                <Form.Label>Dorsal</Form.Label>
                <Form.Control
                  type="number"
                  value={form.shirt_number}
                  onChange={(e) => setForm((s) => ({ ...s, shirt_number: e.target.value }))}
                  disabled={busy}
                  placeholder="Opcional"
                />
              </Form.Group>
            </div>
          </Modal.Body>

          <Modal.Footer>
            <Button variant="secondary" onClick={closeModal} disabled={busy}>
              Cancelar
            </Button>
            <Button variant="primary" type="submit" disabled={busy}>
              {busy ? (
                <>
                  <Spinner animation="border" size="sm" /> Guardando...
                </>
              ) : (
                "Guardar"
              )}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
