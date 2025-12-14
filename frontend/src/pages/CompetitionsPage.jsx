import { useEffect, useMemo, useRef, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import api from "../api/api";
import FiltersBar from "../components/Competitions/FiltersBar";
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

  const cancelRef = useRef(null);

  // Query params desde URL
  const qp = useMemo(() => {
    const region   = searchParams.get("region")   || "";
    const season   = searchParams.get("season")   || FALLBACK_SEASONS[0];
    const search   = searchParams.get("search")   || "";
    const gender   = searchParams.get("gender")   || "";
    const level    = searchParams.get("level")    || "";    // "amateur" | "semi"
    const province = searchParams.get("province") || "";
    const category = searchParams.get("category") || "";
    const page     = parseInt(searchParams.get("page") || "1", 10);
    const per_page = parseInt(searchParams.get("per_page") || DEFAULT_PER_PAGE, 10);
    return { region, season, search, gender, level, province, category, page, per_page };
  }, [searchParams]);

  // Helper para actualizar URL
  const setParams = (partial) => {
    const filtersChanged =
      (partial.region   ?? qp.region)   !== qp.region   ||
      (partial.season   ?? qp.season)   !== qp.season   ||
      (partial.search   ?? qp.search)   !== qp.search   ||
      (partial.gender   ?? qp.gender)   !== qp.gender   ||
      (partial.province ?? qp.province) !== qp.province ||
      (partial.category ?? qp.category) !== qp.category;

    const next = {
      ...qp,
      ...partial,
      page: partial.page ?? (filtersChanged ? 1 : qp.page),
    };

    const params = new URLSearchParams();
    if (next.level)    params.set("level", next.level);   // mantenemos modo
    if (next.region)   params.set("region", next.region);
    if (next.season)   params.set("season", next.season);
    if (next.search)   params.set("search", next.search);
    if (next.gender)   params.set("gender", next.gender);
    if (next.province) params.set("province", next.province);
    if (next.category) params.set("category", next.category);
    params.set("page", String(next.page || 1));
    params.set("per_page", String(next.per_page || DEFAULT_PER_PAGE));

    const incoming = params.toString();
    const current  = window.location.search.slice(1);
    if (incoming !== current) {
      navigate({ pathname: "/ligas", search: `?${incoming}` }, { replace: true });
    }
  };

  // Borra únicamente los filtros del FiltersBar cuando es un reload (F5)
  useEffect(() => {
    const nav = performance.getEntriesByType?.('navigation')?.[0];
    const isReload = nav ? nav.type === 'reload' : performance.navigation?.type === 1; // fallback

    if (!isReload) return;

    const FILTERBAR_KEYS = ['region', 'season', 'search', 'gender', 'province'];
    const params = new URLSearchParams(window.location.search);
    let changed = false;

    FILTERBAR_KEYS.forEach((k) => {
      if (params.has(k)) { params.delete(k); changed = true; }
    });

    // Si quieres, puedes forzar page=1 cuando reseteas filtros:
    if (params.get('page')) { params.set('page', '1'); changed = true; }

    if (changed) {
      navigate(
        { pathname: '/ligas', search: params.toString() ? `?${params.toString()}` : '' },
        { replace: true }
      );
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Cargar CCAA
  useEffect(() => {
    api.get("/api/regions")
      .then((r) => setRegions(Array.isArray(r.data) ? r.data : (r.data?.data || [])))
      .catch(() => setRegions([]));
  }, []);

  // Cargar seasons (o fallback)
  useEffect(() => {
    let mounted = true;
    api.get("/api/seasons")
      .then((r) => {
        const opts = (r.data || []).map(x => x.code).sort().reverse();
        if (mounted && opts.length) setSeasons(opts);
      })
      .catch(() => {});
    return () => { mounted = false; };
  }, []);

  // Petición de competiciones
  useEffect(() => {
    setLoading(true);
    setError("");

    if (cancelRef.current) cancelRef.current.cancel("route-change");
    const source = axios.CancelToken.source();
    cancelRef.current = source;

    api.get("/api/competitions", {
      cancelToken: source.token,
      params: {
        level:    qp.level   || undefined,
        region:   qp.region  || undefined,
        season:   qp.season  || undefined,
        search:   qp.search  || undefined,
        gender:   qp.gender  || undefined,
        province: qp.province|| undefined,
        category: qp.category|| undefined,
        page:     qp.page    || 1,
        per_page: qp.per_page|| DEFAULT_PER_PAGE,
      },
    })
    .then((r) => {
      setItems(r.data?.data || []);
      setMeta(r.data?.meta || { page: 1, per_page: DEFAULT_PER_PAGE, total: 0 });
    })
    .catch((err) => {
      if (axios.isCancel(err)) return;
      const status = err?.response?.status;
      setError(status === 422 ? "Parámetros inválidos. Revisa los filtros." : "No se pudo cargar el catálogo.");
      setItems([]);
      setMeta({ page: 1, per_page: DEFAULT_PER_PAGE, total: 0 });
    })
    .finally(() => setLoading(false));

    return () => source.cancel("route-change");
  }, [qp.region, qp.season, qp.search, qp.gender, qp.level, qp.province, qp.category, qp.page, qp.per_page]);

  const handleFilterChange = (next) => setParams(next);
  const handlePageChange   = (p)   => setParams({ page: p });

  // Reinicializa filtros al cambiar de modo (amateur/semi)
  const filtersKey = `mode-${qp.level || "all"}`;

  return (
    <div className="container py-4">
      <FiltersBar
        key={filtersKey}
        mode={qp.level || ""}             // "amateur" | "semi" | ""
        regions={regions}
        seasonOptions={seasons}
        initial={qp}
        onChange={handleFilterChange}
      />

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
        <div className="alert alert-danger mt-3" role="alert">{error}</div>
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
