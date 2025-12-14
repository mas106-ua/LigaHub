import { useEffect, useMemo, useRef, useState } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import api from "../../api/api";
import useDebounce from "../../hooks/useDebounce";
import Pagination from "../../components/Competitions/Pagination";
import { fetchAdminCompetitionLeagues, fetchAdminCompetitions } from "../../api/adminCompetitions";

const LEVEL_OPTS = [
  { value: "", label: "Todos" },
  { value: "pro", label: "Profesional" },
  { value: "semi", label: "Semiprofesional" },
  { value: "amateur", label: "Amateur" },
];

const GENDER_OPTS = [
  { value: "", label: "Todos" },
  { value: "male", label: "Masculino" },
  { value: "female", label: "Femenino" },
  { value: "mixed", label: "Mixto" },
];

const CATEGORY_OPTS = [
  { value: "", label: "Todas" },
  { value: "Senior", label: "Senior" },
  { value: "Juvenil", label: "Juvenil" },
];

const DEFAULT_PER_PAGE = 12;

export default function AdminOfficialCompetitionsPage() {
  const cancelRef = useRef(null);

  const [regions, setRegions] = useState([]);
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({ page: 1, per_page: DEFAULT_PER_PAGE, total: 0 });

  const [openId, setOpenId] = useState(null);
  const [leaguesByCompetition, setLeaguesByCompetition] = useState({}); // { [competitionId]: {loading, rows, error} }

  const [search, setSearch] = useState("");
  const [level, setLevel] = useState("");
  const [gender, setGender] = useState("");
  const [category, setCategory] = useState("");
  const [region, setRegion] = useState("");

  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(DEFAULT_PER_PAGE);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const debouncedSearch = useDebounce(search, 350);

  // cargar CCAA para filtro
  useEffect(() => {
    api.get("/api/regions")
      .then((r) => setRegions(Array.isArray(r.data) ? r.data : (r.data?.data || [])))
      .catch(() => setRegions([]));
  }, []);

  // si cambia un filtro: volver a page 1
  useEffect(() => {
    setPage(1);
  }, [debouncedSearch, level, gender, category, region]);

  const params = useMemo(
    () => ({
      search: debouncedSearch || undefined,
      level: level || undefined,
      gender: gender || undefined,
      category: category || undefined,
      region: region || undefined,
      page,
      per_page: perPage,
    }),
    [debouncedSearch, level, gender, category, region, page, perPage]
  );

  // listar competiciones
  useEffect(() => {
    setLoading(true);
    setError("");

    if (cancelRef.current) cancelRef.current.cancel("route-change");
    const source = axios.CancelToken.source();
    cancelRef.current = source;

    fetchAdminCompetitions(params, { cancelToken: source.token })
      .then((payload) => {
        const data = payload?.data || [];
        setItems(Array.isArray(data) ? data : []);
        setMeta(payload?.meta || { page: 1, per_page: perPage, total: 0 });
      })
      .catch((err) => {
        if (axios.isCancel(err)) return;
        const status = err?.response?.status;
        setError(status === 403 ? "No tienes permisos para ver este panel." : "No se pudieron cargar las competiciones.");
        setItems([]);
        setMeta({ page: 1, per_page: perPage, total: 0 });
      })
      .finally(() => setLoading(false));

    return () => source.cancel("route-change");
  }, [params, perPage]);

  const toggleCompetition = async (competitionId) => {
    setError("");
    setOpenId((cur) => (cur === competitionId ? null : competitionId));

    if (leaguesByCompetition[competitionId]?.rows) return;

    setLeaguesByCompetition((cur) => ({
      ...cur,
      [competitionId]: { loading: true, rows: null, error: "" },
    }));

    try {
      const payload = await fetchAdminCompetitionLeagues(competitionId);
      const rows = payload?.data || [];
      setLeaguesByCompetition((cur) => ({
        ...cur,
        [competitionId]: { loading: false, rows: Array.isArray(rows) ? rows : [], error: "" },
      }));
    } catch (e) {
      setLeaguesByCompetition((cur) => ({
        ...cur,
        [competitionId]: { loading: false, rows: [], error: "No se pudieron cargar las ediciones." },
      }));
    }
  };

  return (
    <div className="container py-4">
      <div className="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-3">
        <div>
          <h1 className="h4 mb-1">Panel admin · Competiciones oficiales</h1>
          <div className="text-muted small">
            Lista de competiciones y sus ediciones (temporadas / grupos). Accede rápido a la gestión de resultados y partidos.
          </div>
        </div>

        <div className="d-flex gap-2">
          <Link className="btn btn-outline-secondary btn-sm" to="/ligas">
            Ver catálogo público
          </Link>
        </div>
      </div>

      {/* filtros */}
      <div className="card border-0 shadow-sm mb-3">
        <div className="card-body">
          <div className="row g-3">
            <div className="col-12 col-lg-4">
              <label className="form-label">Buscar</label>
              <input
                className="form-control"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Nombre o código…"
              />
            </div>

            <div className="col-6 col-lg-2">
              <label className="form-label">Nivel</label>
              <select className="form-select" value={level} onChange={(e) => setLevel(e.target.value)}>
                {LEVEL_OPTS.map((o) => (
                  <option key={o.value} value={o.value}>
                    {o.label}
                  </option>
                ))}
              </select>
            </div>

            <div className="col-6 col-lg-2">
              <label className="form-label">Género</label>
              <select className="form-select" value={gender} onChange={(e) => setGender(e.target.value)}>
                {GENDER_OPTS.map((o) => (
                  <option key={o.value} value={o.value}>
                    {o.label}
                  </option>
                ))}
              </select>
            </div>

            <div className="col-6 col-lg-2">
              <label className="form-label">Categoría</label>
              <select className="form-select" value={category} onChange={(e) => setCategory(e.target.value)}>
                {CATEGORY_OPTS.map((o) => (
                  <option key={o.value} value={o.value}>
                    {o.label}
                  </option>
                ))}
              </select>
            </div>

            <div className="col-6 col-lg-2">
              <label className="form-label">CCAA</label>
              <select className="form-select" value={region} onChange={(e) => setRegion(e.target.value)}>
                <option value="">Todas</option>
                {(Array.isArray(regions) ? regions : []).map((r) => (
                  <option key={r.code} value={r.code}>
                    {r.name}
                  </option>
                ))}
              </select>
            </div>

            <div className="col-12 d-flex justify-content-end">
              <div className="d-flex align-items-center gap-2">
                <label className="form-label mb-0 small text-muted">Por página</label>
                <select
                  className="form-select form-select-sm"
                  style={{ width: 90 }}
                  value={perPage}
                  onChange={(e) => setPerPage(Number(e.target.value))}
                >
                  {[8, 12, 20, 30].map((n) => (
                    <option key={n} value={n}>
                      {n}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      {loading && <div className="text-muted">Cargando competiciones…</div>}

      {!loading && error && <div className="alert alert-danger">{error}</div>}

      {!loading && !error && items.length === 0 && (
        <div className="alert alert-secondary">No hay competiciones con esos filtros.</div>
      )}

      {!loading && !error && items.length > 0 && (
        <>
          <div className="row g-3">
            {items.map((c) => {
              const opened = openId === c.id;
              const leaguesState = leaguesByCompetition[c.id];

              const catName = c.category?.name || c.category_name || "—";
              const regionName = c.region?.name || c.region_name || "";
              const provName = c.province?.name || c.province_name || "";
              const metaText = [
                c.level ? c.level.toUpperCase() : null,
                c.gender ? c.gender.toUpperCase() : null,
                catName && catName !== "—" ? catName : null,
                regionName ? regionName : null,
                provName ? provName : null,
              ]
                .filter(Boolean)
                .join(" · ");

              return (
                <div className="col-12" key={c.id}>
                  <div className="card border-0 shadow-sm">
                    <div className="card-body">
                      <div className="d-flex justify-content-between gap-3 flex-wrap">
                        <div>
                          <div className="d-flex align-items-center gap-2 flex-wrap">
                            <h2 className="h6 mb-0">{c.name}</h2>
                            <span className="badge text-bg-light">{c.code}</span>
                            {typeof c.leagues_count !== "undefined" && (
                              <span className="badge text-bg-secondary">
                                {c.leagues_count} ediciones
                              </span>
                            )}
                          </div>
                          <div className="text-muted small mt-1">{metaText || "—"}</div>
                        </div>

                        <div className="d-flex align-items-center gap-2">
                          <button
                            className="btn btn-outline-primary btn-sm"
                            onClick={() => toggleCompetition(c.id)}
                          >
                            {opened ? "Cerrar" : "Ver ediciones"}
                          </button>
                        </div>
                      </div>

                      {opened && (
                        <div className="mt-3">
                          {leaguesState?.loading && (
                            <div className="text-muted">Cargando ediciones…</div>
                          )}

                          {leaguesState?.error && (
                            <div className="alert alert-warning py-2">{leaguesState.error}</div>
                          )}

                          {!leaguesState?.loading && leaguesState?.rows && (
                            <div className="table-responsive">
                              <table className="table table-sm align-middle mb-0">
                                <thead>
                                  <tr>
                                    <th>Edición (liga)</th>
                                    <th style={{ width: 110 }}>Temporada</th>
                                    <th style={{ width: 140 }}>Grupo</th>
                                    <th style={{ width: 90 }} className="text-end">
                                      Equipos
                                    </th>
                                    <th style={{ width: 90 }} className="text-end">
                                      Partidos
                                    </th>
                                    <th style={{ width: 90 }} className="text-end">
                                      Jugados
                                    </th>
                                    <th style={{ width: 240 }} className="text-end">
                                      Acciones
                                    </th>
                                  </tr>
                                </thead>
                                <tbody>
                                  {(leaguesState?.rows || []).map((l) => (
                                    <tr key={l.id}>
                                      <td>{l.name}</td>
                                      <td>{l.season_code || l.season?.code || "—"}</td>
                                      <td>{l.group_name || "—"}</td>
                                      <td className="text-end">{l.teams_count ?? "—"}</td>
                                      <td className="text-end">{l.matches_total_count ?? "—"}</td>
                                      <td className="text-end">{l.matches_played_count ?? "—"}</td>
                                      <td className="text-end">
                                        <div className="btn-group btn-group-sm">
                                          <Link
                                            className="btn btn-outline-primary"
                                            to={`/admin/ligas/${l.id}`}
                                          >
                                            Gestionar
                                          </Link>
                                        </div>
                                      </td>
                                    </tr>
                                  ))}
                                </tbody>
                              </table>
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          <div className="mt-3">
            <Pagination
              page={meta.page || 1}
              total={meta.total || 0}
              perPage={meta.per_page || perPage}
              onPage={(p) => setPage(p)}
            />
          </div>
        </>
      )}
    </div>
  );
}
