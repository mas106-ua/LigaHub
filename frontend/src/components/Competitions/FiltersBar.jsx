import { useEffect, useMemo, useState, useRef } from "react";
import useDebounce from "../../hooks/useDebounce";

const GENDER_OPTS = [
  { value: "",       label: "Todos" },
  { value: "male",   label: "Masculino" },
  { value: "female", label: "Femenino" },
];

const LEVEL_OPTS = [
  { value: "",       label: "Todos" },
  { value: "pro",    label: "Profesional" },
  { value: "semi",   label: "Semiprofesional" },
  { value: "amateur",label: "Amateur" },
];

export default function FiltersBar({ regions, seasonOptions, initial, onChange }) {
  const [region, setRegion] = useState(initial.region ?? "");
  const [season, setSeason] = useState(initial.season ?? seasonOptions[0]);
  const [search, setSearch] = useState(initial.search ?? "");
  const [gender, setGender] = useState(initial.gender || "");
  const [level,  setLevel]  = useState(initial.level  || "");
  const debouncedSearch = useDebounce(search, 350);

  const firstRun = useRef(true);

  useEffect(() => {
    if (firstRun.current) {
      firstRun.current = false;
      const same =
        (initial.region ?? "") === region &&
        (initial.season ?? seasonOptions[0] ?? "") === season &&
        (initial.search ?? "") === debouncedSearch &&
        (initial.gender ?? "") === gender &&
        (initial.level  ?? "") === level;
      if (same) return; // no actualices URL al montar si no cambia nada
    }
    onChange({ region, season, search: debouncedSearch, gender, level});
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [region, season, debouncedSearch, gender, level, onChange, initial, seasonOptions]);

  const hasFilters = useMemo(() => !!region || (search && search.trim() !== ""), [region, search]);
  const safeRegions = Array.isArray(regions) ? regions : [];

  return (
    <div className="card border-0 mb-3">
      <div className="card-body">
        {/* Fila 1: búsqueda ancho completo */}
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

        {/* Fila 2: selects lado a lado */}
        <div className="row g-3">
          <div className="col-12 col-md-6">
            <label className="form-label">Comunidad Autónoma</label>
            <select className="form-select" value={region} onChange={(e)=>setRegion(e.target.value)}>
              <option value="">Todas</option>
              {safeRegions.map((r) => (
                <option key={r.code} value={r.code}>{r.name}</option>
              ))}
            </select>
          </div>

          <div className="col-12 col-md-6">
            <label className="form-label">Temporada</label>
            <select className="form-select" value={season} onChange={(e)=>setSeason(e.target.value)}>
              {seasonOptions.map((s) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
          </div>

          <div className="col-12 col-md-6">
            <label className="form-label">Género</label>
            <select
              className="form-select"
              value={gender}
              onChange={(e) => setGender(e.target.value)}
            >
              {GENDER_OPTS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
          </div>

          <div className="col-12 col-md-6">
            <label className="form-label">Nivel Competitivo</label>
            <select
              className="form-select"
              value={level}
              onChange={(e) => setLevel(e.target.value)}
            >
              {LEVEL_OPTS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
          </div>
        </div>
      </div>
    </div>
  );
}
