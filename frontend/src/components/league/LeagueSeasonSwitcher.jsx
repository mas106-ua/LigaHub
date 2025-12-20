import { useEffect, useState } from "react";
import { useLocation, useNavigate, useParams } from "react-router-dom";
import { fetchLeagueVersions } from "../../api/leagueVersions";

export default function LeagueSeasonSwitcher() {
  const { leagueId } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const [versions, setVersions] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!leagueId) return;
    let cancelled = false;
    setLoading(true);
    fetchLeagueVersions(leagueId)
      .then((data) => {
        if (!cancelled) setVersions(data.versions || []);
      })
      .catch((err) => console.error("Error al cargar versiones", err))
      .finally(() => { if (!cancelled) setLoading(false); });
    return () => { cancelled = true; };
  }, [leagueId]);

  if (!leagueId || versions.length <= 1) return null;
  const current = String(leagueId);
  const suffix = location.pathname.replace(`/comp/${leagueId}`, "");
  const search = location.search ?? "";
  const handleChange = (e) => {
    const nextId = e.target.value;
    if (nextId && nextId !== current) navigate(`/comp/${nextId}${suffix}${search}`);
  };
  return (
    <div className="mb-3 d-flex justify-content-end">
      <div className="d-flex align-items-center gap-2">
        <label htmlFor="seasonSwitcher" className="mb-0">Temporada:</label>
        <select id="seasonSwitcher"
          className="form-select form-select-sm"
          value={current}
          onChange={handleChange}
          disabled={loading}
        >
          {versions.map(v => (
            <option key={v.league_id} value={v.league_id}>
              {v.season_code || "Sin temporada"}
            </option>
          ))}
        </select>
      </div>
    </div>
  );
}
