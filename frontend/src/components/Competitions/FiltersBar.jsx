import { useEffect, useRef, useState } from "react";
import api from "../../api/api";
import useDebounce from "../../hooks/useDebounce";

const GENDER_OPTS = [
  { value: "", label: "Todos" },
  { value: "male", label: "Masculino" },
  { value: "female", label: "Femenino" },
];

const CATEGORY_OPTS = [
  { value: "", label: "Todas" },
  { value: "Senior", label: "Senior" },
  { value: "Juvenil", label: "Juvenil" },
];

export default function FiltersBar({
  mode = "",
  regions,
  seasonOptions,
  initial,
  onChange,
}) {
  const isAmateur = mode === "amateur";

  const [region, setRegion] = useState(initial.region ?? "");
  const [season, setSeason] = useState(initial.season ?? (seasonOptions[0] || ""));
  const [search, setSearch] = useState(initial.search ?? "");
  const [gender, setGender] = useState(initial.gender ?? "");
  const [province, setProvince] = useState(initial.province ?? "");
  const [category, setCategory] = useState(initial.category ?? "");

  const [provOpts, setProvOpts] = useState([]);
  const [hideProv, setHideProv] = useState(true);

  const debouncedSearch = useDebounce(search, 350);
  const firstRun = useRef(true);

  useEffect(() => {
    if (!isAmateur) {
      setProvOpts([]);
      setHideProv(true);
      setRegion("");
      setProvince("");
      return;
    }

    if (!region) {
      setProvOpts([]);
      setHideProv(true);
      setProvince("");
      return;
    }

    api
      .get("/api/provinces", { params: { region } })
      .then((r) => {
        const list = Array.isArray(r.data) ? r.data : r.data?.data || [];
        setProvOpts(list);
        setHideProv(list.length <= 1);

        if (province && !list.some((p) => p.code === province)) {
          setProvince("");
        }
      })
      .catch(() => {
        setProvOpts([]);
        setHideProv(true);
        setProvince("");
      });
  }, [region, isAmateur, province]);

  useEffect(() => {
    if (firstRun.current) {
      firstRun.current = false;
    }

    onChange({
      region: isAmateur ? region : "",
      season,
      search: debouncedSearch,
      gender,
      province: isAmateur ? province : "",
      category,
    });
  }, [
    region,
    season,
    debouncedSearch,
    gender,
    province,
    isAmateur,
    category,
    onChange,
  ]);

  const handleRegionChange = (value) => {
    setRegion(value);
    setProvince("");
  };

  const safeRegions = Array.isArray(regions) ? regions : [];
  const showProvince = isAmateur && !hideProv;

  return (
    <div className="card border-0 mb-3 app-filter-card">
      <div className="card-body">
        <div className="app-filter-grid">
          <div className="app-filter-field app-filter-field--search">
            <label htmlFor="competition-search" className="form-label">
              Buscar
            </label>
            <input
              id="competition-search"
              type="text"
              className="form-control"
              placeholder="Nombre de liga…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>

          {isAmateur && (
            <div className="app-filter-field app-filter-field--half">
              <label htmlFor="competition-region" className="form-label">
                Comunidad Autónoma
              </label>
              <select
                id="competition-region"
                className="form-select"
                value={region}
                onChange={(e) => handleRegionChange(e.target.value)}
              >
                <option value="">Todas</option>
                {safeRegions.map((r) => (
                  <option key={r.code} value={r.code}>
                    {r.name}
                  </option>
                ))}
              </select>
            </div>
          )}

          <div className="app-filter-field app-filter-field--half">
            <label htmlFor="competition-season" className="form-label">
              Temporada
            </label>
            <select
              id="competition-season"
              className="form-select"
              value={season}
              onChange={(e) => setSeason(e.target.value)}
            >
              {seasonOptions.map((s) => (
                <option key={s} value={s}>
                  {s}
                </option>
              ))}
            </select>
          </div>

          {showProvince && (
            <div className="app-filter-field app-filter-field--half">
              <label htmlFor="competition-province" className="form-label">
                Provincia
              </label>
              <select
                id="competition-province"
                className="form-select"
                value={province}
                onChange={(e) => setProvince(e.target.value)}
              >
                <option value="">Todas las provincias</option>
                {provOpts.map((p) => (
                  <option key={p.code} value={p.code}>
                    {p.name}
                  </option>
                ))}
              </select>
              <div className="app-filter-help">
                Si no eliges provincia, verás tanto ligas autonómicas como
                provinciales.
              </div>
            </div>
          )}

          <div className="app-filter-field app-filter-field--half">
            <label htmlFor="competition-gender" className="form-label">
              Género
            </label>
            <select
              id="competition-gender"
              className="form-select"
              value={gender}
              onChange={(e) => setGender(e.target.value)}
            >
              {GENDER_OPTS.map((o) => (
                <option key={o.value} value={o.value}>
                  {o.label}
                </option>
              ))}
            </select>
          </div>

          <div className="app-filter-field app-filter-field--half">
            <label htmlFor="competition-category" className="form-label">
              Categoría
            </label>
            <select
              id="competition-category"
              className="form-select"
              value={category}
              onChange={(e) => setCategory(e.target.value)}
            >
              {CATEGORY_OPTS.map((o) => (
                <option key={o.value} value={o.value}>
                  {o.label}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>
    </div>
  );
}