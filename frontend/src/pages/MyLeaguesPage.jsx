import { useEffect, useMemo, useState } from "react";
import { Alert, Badge, Button, Card, Form, Spinner } from "react-bootstrap";
import { Link, useLocation } from "react-router-dom";
import { getMyPrivateLeagues } from "../api/myLeagues";

const ROLE_OPTIONS = [
  { value: "", label: "Todos los roles" },
  { value: "owner", label: "Propietario" },
  { value: "admin", label: "Administrador" },
  { value: "member", label: "Miembro" },
  { value: "viewer", label: "Solo lectura" },
];

function roleVariant(role) {
  if (role === "owner") return "primary";
  if (role === "admin") return "success";
  if (role === "member") return "secondary";
  if (role === "viewer") return "info";
  return "secondary";
}

function roleLabel(league) {
  return league.role_label || league.role_in_league || "Miembro";
}

function normalizeLeagueStats(league) {
  const stats = league.stats || {};

  return {
    teams: stats.teams_count ?? league.teams_count ?? null,
    matches: stats.matches_count ?? league.matches_count ?? null,
  };
}

function PrivateLeagueCard({ league }) {
  const stats = normalizeLeagueStats(league);

  return (
    <article className="app-my-league-card">
      <div className="app-my-league-card__main">
        <div className="app-my-league-card__header">
          <h3 className="app-my-league-card__title">{league.name}</h3>

          <Badge bg={roleVariant(league.role_in_league)}>
            {roleLabel(league)}
          </Badge>
        </div>

        <div className="app-my-league-card__meta">
          {league.season?.code && <span>Temporada {league.season.code}</span>}
          {league.category?.name && <span>{league.category.name}</span>}
          {league.region?.name && <span>{league.region.name}</span>}
        </div>

        <div className="app-my-league-card__badges">
          {typeof stats.teams === "number" && (
            <span className="badge text-bg-light">Equipos: {stats.teams}</span>
          )}

          {typeof stats.matches === "number" && (
            <span className="badge text-bg-light">
              Partidos: {stats.matches}
            </span>
          )}

          {league.can_manage && (
            <span className="badge text-bg-success">Gestión habilitada</span>
          )}
        </div>
      </div>

      <div className="app-my-league-card__actions">
        <Button
          as={Link}
          to={league.links?.detail || `/mis-ligas/${league.id}`}
          variant="outline-primary"
          size="sm"
        >
          Ver liga
        </Button>
      </div>
    </article>
  );
}

function LeagueGroup({ title, description, leagues, emptyText }) {
  return (
    <section className="app-my-leagues-group" aria-labelledby={title}>
      <div className="app-my-leagues-group__header">
        <div>
          <h2 id={title} className="h5 mb-1">
            {title}
          </h2>
          <p className="text-muted mb-0">{description}</p>
        </div>

        <Badge bg="light" text="dark">
          {leagues.length}
        </Badge>
      </div>

      {leagues.length === 0 ? (
        <div className="app-my-leagues-empty">{emptyText}</div>
      ) : (
        <div className="app-my-leagues-list">
          {leagues.map((league) => (
            <PrivateLeagueCard key={league.id} league={league} />
          ))}
        </div>
      )}
    </section>
  );
}

export default function MyLeaguesPage() {
  const location = useLocation();
  const flash = location.state?.flash;

  const [leagues, setLeagues] = useState([]);
  const [meta, setMeta] = useState(null);

  const [search, setSearch] = useState("");
  const [role, setRole] = useState("");
  const [season, setSeason] = useState("");

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const filters = useMemo(
    () => ({
      search: search.trim(),
      role,
      season: season.trim(),
    }),
    [search, role, season]
  );

  const load = async (nextFilters = filters) => {
    setLoading(true);
    setError("");

    try {
      const payload = await getMyPrivateLeagues(nextFilters);
      setLeagues(payload.data);
      setMeta(payload.meta);
    } catch (e) {
      const status = e?.response?.status;

      if (status === 401) {
        setError("Necesitas iniciar sesión para ver tus ligas.");
      } else if (status === 422) {
        setError("Alguno de los filtros introducidos no es válido.");
      } else {
        setError("No se pudieron cargar tus ligas.");
      }

      setLeagues([]);
      setMeta(null);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load(filters);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleSubmitFilters = (event) => {
    event.preventDefault();
    load(filters);
  };

  const clearFilters = () => {
    const emptyFilters = {
      search: "",
      role: "",
      season: "",
    };

    setSearch("");
    setRole("");
    setSeason("");
    load(emptyFilters);
  };

  const managedLeagues = useMemo(
    () => leagues.filter((league) => league.can_manage),
    [leagues]
  );

  const memberLeagues = useMemo(
    () => leagues.filter((league) => !league.can_manage),
    [leagues]
  );

  const hasFilters = !!filters.search || !!filters.role || !!filters.season;

  return (
    <div className="app-private-shell">
      <Card className="shadow-sm border-0 app-page-card">
        <Card.Body className="p-4">
          {flash && <Alert variant="success">{flash}</Alert>}
          {error && <Alert variant="danger">{error}</Alert>}

          <div className="app-page-header">
            <div>
              <h1 className="app-page-title">Mis ligas privadas</h1>
              <p className="app-page-subtitle mb-0">
                Consulta todas las ligas privadas en las que participas y accede
                rápidamente según tu rol.
              </p>
            </div>

            <div className="app-page-actions">
              <Button
                variant="outline-secondary"
                onClick={() => load(filters)}
                disabled={loading}
              >
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

          <Form
            className="app-my-leagues-filters"
            onSubmit={handleSubmitFilters}
          >
            <Form.Group className="app-my-leagues-filters__field">
              <Form.Label>Buscar</Form.Label>
              <Form.Control
                type="search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Nombre de la liga..."
              />
            </Form.Group>

            <Form.Group className="app-my-leagues-filters__field">
              <Form.Label>Rol</Form.Label>
              <Form.Select
                value={role}
                onChange={(event) => setRole(event.target.value)}
              >
                {ROLE_OPTIONS.map((option) => (
                  <option key={option.value || "all"} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </Form.Select>
            </Form.Group>

            <Form.Group className="app-my-leagues-filters__field">
              <Form.Label>Temporada</Form.Label>
              <Form.Control
                type="text"
                value={season}
                onChange={(event) => setSeason(event.target.value)}
                placeholder="Ej. 2025/26"
              />
            </Form.Group>

            <div className="app-my-leagues-filters__actions">
              <Button type="submit" variant="primary" disabled={loading}>
                Filtrar
              </Button>

              <Button
                type="button"
                variant="outline-secondary"
                onClick={clearFilters}
                disabled={loading || !hasFilters}
              >
                Limpiar
              </Button>
            </div>
          </Form>

          <div className="app-my-leagues-summary">
            <span>
              {loading
                ? "Cargando ligas..."
                : `${meta?.total ?? leagues.length} liga${
                    (meta?.total ?? leagues.length) === 1 ? "" : "s"
                  } encontrada${(meta?.total ?? leagues.length) === 1 ? "" : "s"}`}
            </span>

            {hasFilters && (
              <span className="text-muted">
                Filtros aplicados
              </span>
            )}
          </div>

          {loading && (
            <div className="d-flex align-items-center gap-2 mt-3">
              <Spinner animation="border" size="sm" />
              <span>Cargando ligas…</span>
            </div>
          )}

          {!loading && !error && leagues.length === 0 && (
            <div className="alert alert-secondary mt-3 mb-0">
              {hasFilters
                ? "No hay ligas privadas que coincidan con los filtros."
                : "Aún no tienes ligas privadas."}
            </div>
          )}

          {!loading && !error && leagues.length > 0 && (
            <div className="app-my-leagues-groups mt-3">
              <LeagueGroup
                title="Ligas que administras"
                description="Ligas donde puedes gestionar configuración, calendario, equipos o invitaciones."
                leagues={managedLeagues}
                emptyText="No administras ninguna liga con los filtros actuales."
              />

              <LeagueGroup
                title="Ligas donde participas"
                description="Ligas privadas en las que tienes acceso como miembro o solo lectura."
                leagues={memberLeagues}
                emptyText="No participas como miembro en ninguna liga con los filtros actuales."
              />
            </div>
          )}
        </Card.Body>
      </Card>
    </div>
  );
}