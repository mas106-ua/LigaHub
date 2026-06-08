import { useEffect, useMemo, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../api/api";
import { getMyPrivateLeagues } from "../api/myLeagues";
import { useAuth } from "../context/AuthContext";
import LeagueMeta from "../components/league/LeagueMeta";

const LEVELS = [
  {
    key: "pro",
    title: "Profesional",
    description: "Competiciones de ámbito profesional.",
    to: "/ligas?level=pro",
  },
  {
    key: "semi",
    title: "Semiprofesional",
    description: "Competiciones semiprofesionales y categorías intermedias.",
    to: "/ligas?level=semi",
  },
  {
    key: "amateur",
    title: "Amateur",
    description: "Competiciones territoriales y ligas de ámbito amateur.",
    to: "/ligas?level=amateur",
  },
];

function getPrivateLeaguePath(league) {
  return league.links?.detail || `/mis-ligas/${league.id}`;
}

function getPrivateLeagueMeta(league) {
  return [
    league.season?.code ? `Temporada ${league.season.code}` : null,
    league.category?.name,
    league.region?.name,
  ]
    .filter(Boolean)
    .join(" · ");
}

function CompetitionLevelCard({ block }) {
  return (
    <article className="app-home-card app-home-level-card">
      <div className="app-home-card__header">
        <div>
          <h2>{block.title}</h2>
          <p>{block.description}</p>
        </div>

        <span className="app-home-count">
          {block.loading ? "…" : block.total}
        </span>
      </div>

      <div className="app-home-mini-list">
        {block.loading &&
          Array.from({ length: 3 }).map((_, index) => (
            <div key={index} className="app-home-skeleton" />
          ))}

        {!block.loading && block.error && (
          <p className="app-home-muted mb-0">No se pudo cargar este bloque.</p>
        )}

        {!block.loading && !block.error && block.items.length === 0 && (
          <p className="app-home-muted mb-0">
            No hay competiciones disponibles.
          </p>
        )}

        {!block.loading &&
          !block.error &&
          block.items.map((league) => (
            <Link
              key={league.id}
              to={`/comp/${league.id}`}
              className="app-home-mini-item"
            >
              <strong>{league.name}</strong>
              <LeagueMeta league={league} compact />
            </Link>
          ))}
      </div>

      <Link to={block.to} className="btn btn-outline-primary w-100 mt-auto">
        Ver competiciones
      </Link>
    </article>
  );
}

function MyLeaguesBlock({ user }) {
  const [loading, setLoading] = useState(Boolean(user));
  const [error, setError] = useState("");
  const [leagues, setLeagues] = useState([]);

  useEffect(() => {
    if (!user) return;

    let mounted = true;

    async function loadMyLeagues() {
      setLoading(true);
      setError("");

      try {
        const payload = await getMyPrivateLeagues();

        if (mounted) {
          setLeagues(payload.data || []);
        }
      } catch {
        if (mounted) {
          setError("No se pudieron cargar tus ligas privadas.");
          setLeagues([]);
        }
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    }

    loadMyLeagues();

    return () => {
      mounted = false;
    };
  }, [user]);

  if (!user) {
    return (
      <section className="app-home-private app-home-private--guest">
        <div>
          <span className="app-home-eyebrow">Mis ligas privadas</span>
          <h2>Accede a tus competiciones privadas</h2>
          <p>
            Inicia sesión para crear ligas, gestionar equipos, generar
            calendarios, registrar resultados e invitar participantes.
          </p>
        </div>

        <div className="app-home-actions">
          <Link to="/login" className="btn btn-primary">
            Iniciar sesión
          </Link>
          <Link to="/register" className="btn btn-outline-primary">
            Crear cuenta
          </Link>
        </div>
      </section>
    );
  }

  const managed = leagues.filter((league) => league.can_manage);
  const preview = leagues.slice(0, 3);

  return (
    <section className="app-home-private">
      <div className="app-home-private__header">
        <div>
          <span className="app-home-eyebrow">Mis ligas privadas</span>
          <h2>Panel rápido de ligas privadas</h2>
          <p>
            Accede a las ligas que administras o en las que participas.
          </p>
        </div>

        <div className="app-home-private__stats">
          <div>
            <strong>{loading ? "…" : leagues.length}</strong>
            <span>Total</span>
          </div>
          <div>
            <strong>{loading ? "…" : managed.length}</strong>
            <span>Administras</span>
          </div>
        </div>
      </div>

      {error && <div className="alert alert-danger mb-3">{error}</div>}

      {loading && (
        <div className="app-home-private__list">
          <div className="app-home-skeleton" />
          <div className="app-home-skeleton" />
          <div className="app-home-skeleton" />
        </div>
      )}

      {!loading && !error && leagues.length === 0 && (
        <div className="app-home-empty">
          Aún no tienes ligas privadas. Cuando crees una liga o aceptes una
          invitación, aparecerá aquí.
        </div>
      )}

      {!loading && !error && preview.length > 0 && (
        <div className="app-home-private__list">
          {preview.map((league) => (
            <Link
              key={league.id}
              to={getPrivateLeaguePath(league)}
              className="app-home-private-item"
            >
              <div>
                <strong>{league.name}</strong>
                <span>{getPrivateLeagueMeta(league) || "Liga privada"}</span>
              </div>

              <span className="badge text-bg-light">
                {league.role_label || league.role_in_league || "Miembro"}
              </span>
            </Link>
          ))}
        </div>
      )}

      <div className="app-home-actions app-home-actions--end">
        <Link to="/mis-ligas" className="btn btn-primary">
          Ver mis ligas
        </Link>
        <Link to="/mis-ligas/crear" className="btn btn-outline-primary">
          Crear liga
        </Link>
      </div>
    </section>
  );
}

export default function Home() {
  const navigate = useNavigate();
  const { user } = useAuth();

  const [season, setSeason] = useState("2025/26");
  const [blocks, setBlocks] = useState(() =>
    LEVELS.map((level) => ({
      ...level,
      loading: true,
      error: "",
      total: 0,
      items: [],
    }))
  );

  const [query, setQuery] = useState("");
  const [searchLoading, setSearchLoading] = useState(false);
  const [searchError, setSearchError] = useState("");
  const [searchResults, setSearchResults] = useState(null);

  useEffect(() => {
    let mounted = true;

    api
      .get("/api/seasons")
      .then((response) => {
        const seasons = Array.isArray(response.data)
          ? response.data
          : response.data?.data || [];

        const codes = seasons
          .map((item) => item.code)
          .filter(Boolean)
          .sort()
          .reverse();

        if (mounted && codes.length > 0) {
          setSeason(codes[0]);
        }
      })
      .catch(() => {
        // Se mantiene la temporada por defecto.
      });

    return () => {
      mounted = false;
    };
  }, []);

  useEffect(() => {
    let mounted = true;

    async function loadLevel(level) {
      try {
        const firstResponse = await api.get("/api/competitions", {
          params: {
            level: level.key,
            season,
            page: 1,
            per_page: 3,
          },
        });

        let items = firstResponse.data?.data || [];
        let meta = firstResponse.data?.meta || {};

        // Si la temporada seleccionada no tiene datos, se reintenta sin temporada.
        if (items.length === 0) {
          const fallbackResponse = await api.get("/api/competitions", {
            params: {
              level: level.key,
              page: 1,
              per_page: 3,
            },
          });

          items = fallbackResponse.data?.data || [];
          meta = fallbackResponse.data?.meta || {};
        }

        return {
          ...level,
          loading: false,
          error: "",
          total: meta.total ?? items.length,
          items,
        };
      } catch {
        return {
          ...level,
          loading: false,
          error: "No se pudo cargar.",
          total: 0,
          items: [],
        };
      }
    }

    async function loadBlocks() {
      setBlocks((prev) =>
        prev.map((block) => ({
          ...block,
          loading: true,
          error: "",
        }))
      );

      const nextBlocks = await Promise.all(LEVELS.map(loadLevel));

      if (mounted) {
        setBlocks(nextBlocks);
      }
    }

    loadBlocks();

    return () => {
      mounted = false;
    };
  }, [season]);

  const totalCompetitions = useMemo(
    () => blocks.reduce((acc, block) => acc + Number(block.total || 0), 0),
    [blocks]
  );

  async function handleGlobalSearch(event) {
    event.preventDefault();

    const term = query.trim();

    if (!term) {
      setSearchResults(null);
      setSearchError("");
      return;
    }

    setSearchLoading(true);
    setSearchError("");

    try {
      const officialPromise = api.get("/api/competitions", {
        params: {
          search: term,
          page: 1,
          per_page: 5,
        },
      });

      const privatePromise = user
        ? getMyPrivateLeagues({ search: term })
        : Promise.resolve({ data: [] });

      const [officialResponse, privatePayload] = await Promise.all([
        officialPromise,
        privatePromise,
      ]);

      setSearchResults({
        term,
        official: officialResponse.data?.data || [],
        private: privatePayload.data || [],
      });
    } catch {
      setSearchError("No se pudo completar la búsqueda.");
      setSearchResults(null);
    } finally {
      setSearchLoading(false);
    }
  }

  function goToOfficialSearch() {
    const term = query.trim();

    if (!term) return;

    navigate(
      `/ligas?search=${encodeURIComponent(term)}&page=1&per_page=8`
    );
  }

  return (
    <div className="container py-4">
      <section className="app-home-hero">
        <div className="app-home-hero__text">
          <span className="app-home-eyebrow">LigaHub</span>
          <h1>Busca competiciones y gestiona tus ligas privadas</h1>
          <p>
            Accede a competiciones oficiales por nivel y consulta rápidamente
            tus ligas privadas desde un único punto de entrada.
          </p>
        </div>

        <form className="app-home-search" onSubmit={handleGlobalSearch}>
          <label htmlFor="home-global-search" className="form-label">
            Buscador global
          </label>

          <div className="app-home-search__row">
            <input
              id="home-global-search"
              className="form-control form-control-lg"
              type="search"
              placeholder="Buscar por nombre de competición o liga..."
              value={query}
              onChange={(event) => setQuery(event.target.value)}
            />

            <button
              type="submit"
              className="btn btn-primary"
              disabled={searchLoading}
            >
              {searchLoading ? "Buscando..." : "Buscar"}
            </button>
          </div>

          <p className="app-home-search__hint">
            Busca en competiciones oficiales y, si has iniciado sesión, también
            en tus ligas privadas.
          </p>
        </form>

        {searchError && (
          <div className="alert alert-danger mt-3 mb-0">{searchError}</div>
        )}

        {searchResults && (
          <div className="app-home-search-results">
            <div className="app-home-search-results__header">
              <h2>Resultados para “{searchResults.term}”</h2>

              <button
                type="button"
                className="btn btn-sm btn-outline-primary"
                onClick={goToOfficialSearch}
              >
                Ver búsqueda en catálogo
              </button>
            </div>

            <div className="app-home-search-results__grid">
              <div>
                <h3>Competiciones oficiales</h3>

                {searchResults.official.length === 0 ? (
                  <p className="app-home-muted mb-0">
                    No se encontraron competiciones oficiales.
                  </p>
                ) : (
                  <div className="app-home-mini-list">
                    {searchResults.official.map((league) => (
                      <Link
                        key={league.id}
                        to={`/comp/${league.id}`}
                        className="app-home-mini-item"
                      >
                        <strong>{league.name}</strong>
                        <LeagueMeta league={league} compact />
                      </Link>
                    ))}
                  </div>
                )}
              </div>

              <div>
                <h3>Mis ligas privadas</h3>

                {!user ? (
                  <p className="app-home-muted mb-0">
                    Inicia sesión para buscar en tus ligas privadas.
                  </p>
                ) : searchResults.private.length === 0 ? (
                  <p className="app-home-muted mb-0">
                    No se encontraron ligas privadas.
                  </p>
                ) : (
                  <div className="app-home-mini-list">
                    {searchResults.private.slice(0, 5).map((league) => (
                      <Link
                        key={league.id}
                        to={getPrivateLeaguePath(league)}
                        className="app-home-mini-item"
                      >
                        <strong>{league.name}</strong>
                        <span>
                          {getPrivateLeagueMeta(league) || "Liga privada"}
                        </span>
                      </Link>
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        )}
      </section>

      <section className="app-home-section">
        <div className="app-home-section__header">
          <div>
            <span className="app-home-eyebrow">Competiciones disponibles</span>
            <h2>Explora el catálogo oficial</h2>
          </div>

          <span className="app-home-section__summary">
            {totalCompetitions > 0
              ? `${totalCompetitions} competiciones encontradas`
              : "Catálogo de competiciones"}
          </span>
        </div>

        <div className="app-home-level-grid">
          {blocks.map((block) => (
            <CompetitionLevelCard key={block.key} block={block} />
          ))}
        </div>
      </section>

      <MyLeaguesBlock user={user} />
    </div>
  );
}