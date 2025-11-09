import { useEffect, useMemo, useRef, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import api from "../api/api";
import FiltersBar from "../components/Competitions/FiltersBar";
import CompetitionCard from "../components/Competitions/CompetitionCard";
import CompetitionCardWide from "../components/Competitions/CompetitionCardWide";
import Pagination from "../components/Competitions/Pagination";
import SkeletonCard from "../components/Competitions/SkeletonCard";
import axios from "axios";

const DEFAULT_PER_PAGE = 8;
const FALLBACK_SEASONS = ["2025/26", "2024/25"];

export default function CompetitionsPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  const [regions, setRegions] = useState([]);
  const [seasons, setSeasons] = useState(FALLBACK_SEASONS);
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({ page: 1, per_page: DEFAULT_PER_PAGE, total: 0 });

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const abortRef = useRef(null);

  const qp = useMemo(() => {
    const region = searchParams.get("region") || "";
    const season = searchParams.get("season") || seasons[0];
    const search = searchParams.get("search") || "";
    const gender   = searchParams.get("gender") || "";   // NEW
    const level    = searchParams.get("level")  || "";   // NEW
    const page = parseInt(searchParams.get("page") || "1", 10);
    const per_page = parseInt(searchParams.get("per_page") || DEFAULT_PER_PAGE, 10);
    return { region, season, search, gender, level, page, per_page };
  }, [searchParams, seasons]);

  const setParams = (partial) => {
        // si no viene page en 'partial' y han cambiado filtros, resetea a 1
        const filtersChanged =
            (partial.region ?? qp.region) !== qp.region ||
            (partial.season ?? qp.season) !== qp.season ||
            (partial.search ?? qp.search) !== qp.search ||
            (partial.gender ?? qp.gender) !== qp.gender ||      
            (partial.level  ?? qp.level ) !== qp.level; 

        const next = {
            ...qp,
            ...partial,
            page: partial.page ?? (filtersChanged ? 1 : qp.page),
        };

        const params = new URLSearchParams();
        if (next.region) params.set("region", next.region);
        if (next.season) params.set("season", next.season);
        if (next.search) params.set("search", next.search);
        if (next.gender) params.set("gender", next.gender);   
        if (next.level)  params.set("level",  next.level); 
        params.set("page", String(next.page || 1));
        params.set("per_page", String(next.per_page || DEFAULT_PER_PAGE));

        const current = window.location.search.slice(1);
        const incoming = params.toString();
        if (current !== incoming) {
            navigate({ pathname: "/ligas", search: `?${incoming}` }, { replace: true });
        }
    };

  useEffect(() => {
    api.get("/api/regions")
        .then((r) => {
        const payload = r.data;
        const list = Array.isArray(payload) ? payload : (payload?.data || []);
        setRegions(list);
        })
        .catch(() => setRegions([]));
    }, []);

  // Opcional: seasons desde API; si no existe el endpoint, nos quedamos con FALLBACK
  useEffect(() => {
    let mounted = true;
    api.get("/api/seasons")
      .then((r) => {
        const opts = (r.data || []).map((x) => x.code).sort().reverse();
        if (mounted && opts.length) setSeasons(opts);
      })
      .catch(() => {});
    return () => { mounted = false; };
  }, []);

  useEffect(() => {
    setLoading(true);
    setError("");

    const source = axios.CancelToken.source();


    if (abortRef.current) abortRef.current.abort();
    abortRef.current = new AbortController();

    api.get("/api/competitions", {
        cancelToken: source.token,
        params: {
        region: qp.region || undefined,
        season: qp.season || undefined,
        search: qp.search || undefined,
        gender: qp.gender || undefined,
        level:  qp.level  || undefined,
        page: qp.page || 1,
        per_page: qp.per_page || DEFAULT_PER_PAGE,
        },
    })
        .then((r) => {
        setItems(r.data?.data || []);
        setMeta(r.data?.meta || { page: 1, per_page: DEFAULT_PER_PAGE, total: 0 });
        })
        .catch((err) => {
        if (axios.isCancel(err)) return; // cancelación esperada
        const status = err?.response?.status;
        setError(status === 422 ? "Parámetros inválidos. Revisa los filtros." : "No se pudo cargar el catálogo.");
        setItems([]);
        setMeta({ page: 1, per_page: DEFAULT_PER_PAGE, total: 0 });
        // opcional: console.error(err);
        })
        .finally(() => setLoading(false));

    return () => source.cancel("route-change");
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [qp.region, qp.season, qp.search, qp.gender, qp.level, qp.page, qp.per_page]);

  const handleFilterChange = (next) => setParams(next);
  const handlePageChange = (p) => setParams({ page: p });

  useEffect(() => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  }, [qp.page]);

  return (
    <div className="container py-4">

      <FiltersBar regions={regions} seasonOptions={seasons} initial={qp} onChange={handleFilterChange} />

      {loading && (
        <div className="row g-3 mt-2">
          {Array.from({ length: DEFAULT_PER_PAGE }).map((_, i) => (
            <div key={i} className="col-12 col-lg-6">
              <SkeletonCard />
            </div>
          ))}
        </div>
      )}

      {!loading && error && (
        <div className="alert alert-danger mt-3" role="alert">
          {error}
        </div>
      )}

      {!loading && !error && items.length === 0 && (
        <div className="alert alert-secondary mt-3" role="alert">
          No hay ligas que coincidan con los filtros seleccionados.
        </div>
      )}

      {!loading && !error && items.length > 0 && (
        <>
          <div className="row g-3 mt-2">
            {items.map((l) => (
              <div className="col-12 col-lg-6" key={l.id}>
                <CompetitionCardWide league={l} />
              </div>
            ))}
          </div>

          <Pagination
            page={meta.page || 1}
            total={meta.total || 0}
            perPage={meta.per_page || DEFAULT_PER_PAGE}
            onPage={handlePageChange}
          />
        </>
      )}
    </div>
  );
}
