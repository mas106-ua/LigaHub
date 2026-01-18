import { useEffect, useMemo, useState } from "react";
import { Alert, Badge, Button, Card, Form, Modal, Spinner, Table } from "react-bootstrap";
import { Link, useNavigate, useParams } from "react-router-dom";
import {
  createPrivateLeagueTeam,
  deletePrivateLeagueTeam,
  getPrivateLeagueTeams,
  updatePrivateLeagueTeam,
} from "../api/privateLeagueTeams";

function pickFirstError(errors, key) {
  const v = errors?.[key];
  if (!v) return "";
  if (Array.isArray(v)) return v[0] || "";
  if (typeof v === "string") return v;
  return "";
}

export default function MyLeagueTeamsPage() {
  const { leagueId } = useParams();
  const navigate = useNavigate();

  const [teams, setTeams] = useState([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [flash, setFlash] = useState("");

  // modal state
  const [showModal, setShowModal] = useState(false);
  const [mode, setMode] = useState("create"); // create | edit
  const [current, setCurrent] = useState(null);
  const [form, setForm] = useState({ name: "", short_name: "", city: "", crest_url: "", group_name: "" });
  const [formErrors, setFormErrors] = useState({});

  const title = useMemo(() => (mode === "create" ? "Nuevo equipo" : "Editar equipo"), [mode]);

  const load = async () => {
    setLoading(true);
    setError("");
    try {
      const items = await getPrivateLeagueTeams(leagueId);
      setTeams(items);
    } catch (e) {
      const status = e?.response?.status;
      if (status === 404) setError("No tienes acceso a esta liga (o no existe).");
      else if (status === 401) setError("Necesitas iniciar sesión.");
      else if (status === 403) setError("No tienes permisos para ver los equipos.");
      else setError("No se pudieron cargar los equipos.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leagueId]);

  const openCreate = () => {
    setMode("create");
    setCurrent(null);
    setForm({ name: "", short_name: "", city: "", crest_url: "", group_name: "" });
    setFormErrors({});
    setShowModal(true);
  };

  const openEdit = (team) => {
    setMode("edit");
    setCurrent(team);
    setForm({
      name: team.name ?? "",
      short_name: team.short_name ?? "",
      city: team.city ?? "",
      crest_url: team.crest_url ?? "",
      group_name: team.group_name ?? "",
    });
    setFormErrors({});
    setShowModal(true);
  };

  const closeModal = () => {
    if (busy) return;
    setShowModal(false);
  };

  const submit = async (e) => {
    e.preventDefault();
    setFlash("");
    setFormErrors({});

    const trimmed = String(form.name || "").trim();
    if (!trimmed) {
      setFormErrors({ name: ["El nombre es obligatorio."] });
      return;
    }

    const payload = {
      name: trimmed,
      short_name: form.short_name?.trim() || null,
      city: form.city?.trim() || null,
      crest_url: form.crest_url?.trim() || null,
      group_name: form.group_name?.trim() || null,
    };

    setBusy(true);
    try {
      if (mode === "create") {
        await createPrivateLeagueTeam(leagueId, payload);
        setFlash("Equipo creado.");
      } else {
        await updatePrivateLeagueTeam(leagueId, current.id, payload);
        setFlash("Equipo actualizado.");
      }
      setShowModal(false);
      await load();
    } catch (e2) {
      if (e2?.response?.status === 422) {
        setFormErrors(e2.response.data?.errors || { general: ["Validación incorrecta."] });
      } else if (e2?.response?.status === 403) {
        setFormErrors({ general: ["No tienes permisos para gestionar equipos en esta liga."] });
      } else {
        setFormErrors({ general: ["No se pudo guardar el equipo."] });
      }
    } finally {
      setBusy(false);
    }
  };

  const onDelete = async (team) => {
    setFlash("");
    setError("");

    const ok = window.confirm(`¿Eliminar "${team.name}" de esta liga?`);
    if (!ok) return;

    setBusy(true);
    try {
      await deletePrivateLeagueTeam(leagueId, team.id);
      setFlash("Equipo eliminado.");
      await load();
    } catch (e) {
      const status = e?.response?.status;
      if (status === 409) setError(e?.response?.data?.message || "No se puede eliminar el equipo.");
      else if (status === 403) setError("No tienes permisos para eliminar equipos.");
      else setError("No se pudo eliminar el equipo.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="d-flex justify-content-center">
      <Card className="shadow-sm border-0" style={{ maxWidth: 1000, width: "100%", borderRadius: "1rem" }}>
        <Card.Body className="p-4">
          <div className="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
              <h4 className="mb-1">Equipos</h4>
              <div className="text-muted" style={{ fontSize: "0.95rem" }}>
                Gestiona los equipos de tu liga privada.
              </div>
            </div>

            <div className="d-flex gap-2">
              <Button as={Link} to={`/mis-ligas/${leagueId}`} variant="outline-secondary" disabled={busy}>
                Volver
              </Button>
              <Button variant="primary" onClick={openCreate} disabled={busy}>
                + Nuevo equipo
              </Button>
            </div>
          </div>

          {flash && <Alert className="mt-3" variant="success">{flash}</Alert>}
          {error && <Alert className="mt-3" variant="danger">{error}</Alert>}

          <hr />

          {loading && (
            <div className="d-flex align-items-center gap-2">
              <Spinner animation="border" size="sm" />
              <span>Cargando equipos…</span>
            </div>
          )}

          {!loading && !error && teams.length === 0 && (
            <div className="text-muted">Aún no has creado equipos.</div>
          )}

          {!loading && !error && teams.length > 0 && (
            <Table responsive hover className="align-middle">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Grupo</th>
                  <th className="text-end" style={{ width: 220 }}>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {teams.map((t) => (
                  <tr key={t.id}>
                    <td>
                      <div className="fw-semibold">{t.name}</div>
                      <div className="text-muted small">
                        {t.short_name ? `Abrev: ${t.short_name} · ` : ""}
                        {t.city ? `Ciudad: ${t.city}` : ""}
                      </div>
                    </td>
                    <td>
                      {t.group_name ? <Badge bg="secondary">{t.group_name}</Badge> : <span className="text-muted">—</span>}
                    </td>
                    <td className="text-end">
                      <Button variant="outline-primary" size="sm" className="me-2" onClick={() => openEdit(t)} disabled={busy}>
                        Editar
                      </Button>
                      <Button variant="outline-danger" size="sm" onClick={() => onDelete(t)} disabled={busy}>
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

      <Modal show={showModal} onHide={closeModal} centered>
        <Form onSubmit={submit}>
          <Modal.Header closeButton={!busy}>
            <Modal.Title>{title}</Modal.Title>
          </Modal.Header>

          <Modal.Body>
            {pickFirstError(formErrors, "general") && (
              <Alert variant="danger">{pickFirstError(formErrors, "general")}</Alert>
            )}

            <Form.Group className="mb-3">
              <Form.Label>Nombre *</Form.Label>
              <Form.Control
                value={form.name}
                onChange={(e) => setForm((p) => ({ ...p, name: e.target.value }))}
                disabled={busy}
                isInvalid={!!pickFirstError(formErrors, "name")}
                placeholder="Nombre del equipo"
              />
              {pickFirstError(formErrors, "name") && (
                <Form.Control.Feedback type="invalid">{pickFirstError(formErrors, "name")}</Form.Control.Feedback>
              )}
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Abreviatura</Form.Label>
              <Form.Control
                value={form.short_name}
                onChange={(e) => setForm((p) => ({ ...p, short_name: e.target.value }))}
                disabled={busy}
                placeholder="Opcional (máx 20)"
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Ciudad</Form.Label>
              <Form.Control
                value={form.city}
                onChange={(e) => setForm((p) => ({ ...p, city: e.target.value }))}
                disabled={busy}
                placeholder="Opcional"
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Escudo (URL)</Form.Label>
              <Form.Control
                value={form.crest_url}
                onChange={(e) => setForm((p) => ({ ...p, crest_url: e.target.value }))}
                disabled={busy}
                placeholder="Opcional"
              />
            </Form.Group>

            <Form.Group>
              <Form.Label>Grupo</Form.Label>
              <Form.Control
                value={form.group_name}
                onChange={(e) => setForm((p) => ({ ...p, group_name: e.target.value }))}
                disabled={busy}
                placeholder="Opcional (p. ej. Grupo A)"
              />
            </Form.Group>
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
