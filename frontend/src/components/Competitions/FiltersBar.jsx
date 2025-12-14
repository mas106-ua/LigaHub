import { useEffect, useMemo, useRef, useState } from "react";
import api from "../../api/api";
import useDebounce from "../../hooks/useDebounce";

const GENDER_OPTS = [
  { value: "",       label: "Todos" },
  { value: "male",   label: "Masculino" },
  { value: "female", label: "Femenino" },
];

const CATEGORY_OPTS = [
  { value: "",        label: "Todas" },
  { value: "Senior",  label: "Senior" },
  { value: "Juvenil", label: "Juvenil" },
];

export default function FiltersBar({ mode = "", regions, seasonOptions, initial, onChange }) {
  const isAmateur = mode === "amateur";

  const [region,   setRegion]   = useState(initial.region   ?? "");
  const [season,   setSeason]   = useState(initial.season   ?? (seasonOptions[0] || ""));
  const [search,   setSearch]   = useState(initial.search   ?? "");
  const [gender,   setGender]   = useState(initial.gender   ?? "");
  const [province, setProvince] = useState(initial.province ?? "");
  const [category, setCategory] = useState(initial.category ?? "");

  const [provOpts, setProvOpts] = useState([]);   // [{id, name, code}]
  const [hideProv, setHideProv] = useState(true); // ocultar select si 0/1 provincias

  const debouncedSearch = useDebounce(search, 350);
  const firstRun = useRef(true);

  // Carga provincias al cambiar CCAA (solo en amateur)
  useEffect(() => {
    if (!isAmateur) {
      setProvOpts([]);
      setHideProv(true);
      setRegion("");     // por si venimos de amateur con región puesta
      setProvince("");
      return;
    }

    if (!region) {
      setProvOpts([]);
      setHideProv(true);
      setProvince("");
      return;
    }

    api.get("/api/provinces", { params: { region } })
      .then((r) => {
        const list = Array.isArray(r.data) ? r.data : (r.data?.data || []);
        setProvOpts(list);
        setHideProv(list.length <= 1);
        // si la provincia actual no pertenece a la lista, resetea
        if (province && !list.some(p => p.code === province)) {
          setProvince("");
        }
      })
      .catch(() => {
        setProvOpts([]);
        setHideProv(true);
        setProvince("");
      });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [region, isAmateur]);

  // Notificar cambios hacia arriba
  useEffect(() => {
    // Evita doble disparo en el primer render si no cambia nada
    if (firstRun.current) {
      firstRun.current = false;
    }
    onChange({
      region:   isAmateur ? region : "",      // en semi no enviamos región/provincia
      season,
      search:   debouncedSearch,
      gender,
      province: isAmateur ? province : "",
      category,
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [region, season, debouncedSearch, gender, province, isAmateur, category, onChange]);

  // Al cambiar de CCAA, resetea provincia
  const handleRegionChange = (val) => {
    setRegion(val);
    setProvince("");
  };

  const safeRegions = Array.isArray(regions) ? regions : [];
  const showProvince = isAmateur && !hideProv;

  return (
    <div className="card border-0 mb-3">
      <div className="card-body">
        {/* Buscar */}
        <div className="mb-3">
          <label className="form-label">Buscar</label>
          <input
            type="text"
            className="form-control"
            placeholder="Nombre de liga…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>

        {/* Fila de selects */}
        <div className="row g-3">
          {/* CCAA (solo amateur) */}
          {isAmateur && (
            <div className="col-12 col-md-6">
              <label className="form-label">Comunidad Autónoma</label>
              <select className="form-select" value={region} onChange={(e)=>handleRegionChange(e.target.value)}>
                <option value="">Todas</option>
                {safeRegions.map((r) => (
                  <option key={r.code} value={r.code}>{r.name}</option>
                ))}
              </select>
            </div>
          )}

          {/* Temporada */}
          <div className={isAmateur ? "col-12 col-md-6" : "col-12 col-md-6"}>
            <label className="form-label">Temporada</label>
            <select className="form-select" value={season} onChange={(e)=>setSeason(e.target.value)}>
              {seasonOptions.map((s) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
          </div>

          {/* Provincia (solo amateur y si hay >1) */}
          {showProvince && (
            <div className="col-12 col-md-6">
              <label className="form-label">Provincia</label>
              <select
                className="form-select"
                value={province}
                onChange={(e)=>setProvince(e.target.value)}
              >
                <option value="">Todas las provincias</option>
                {provOpts.map(p => (
                  <option key={p.code} value={p.code}>{p.name}</option>
                ))}
              </select>
              <div className="form-text">
                Si no eliges provincia, verás tanto ligas autonómicas como provinciales.
              </div>
            </div>
          )}

          {/* Género */}
          <div className="col-12 col-md-6">
            <label className="form-label">Género</label>
            <select className="form-select" value={gender} onChange={(e)=>setGender(e.target.value)}>
              {GENDER_OPTS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
          </div>

          {/* Nuevo select de Categoría */}
          <div className="col-12 col-md-6">
            <label className="form-label">Categoría</label>
            <select
              className="form-select"
              value={category}
              onChange={(e) => setCategory(e.target.value)}
            >
              {CATEGORY_OPTS.map((o) => (
                <option key={o.value} value={o.value}>{o.label}</option>
              ))}
            </select>
          </div>
        </div>
      </div>
    </div>
  );
}
