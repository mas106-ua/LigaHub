import { useEffect, useState } from "react";
import { useParams, useSearchParams } from "react-router-dom";
import { getStandings } from "../../api/standings";

import LeagueHeader from "../../components/league/LeagueHeader";
import api from "../../api/api";

export default function StandingsPage() {
  const { leagueId } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();

  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const matchdayParam = searchParams.get("matchday");
  const groupParam = searchParams.get("group");

  const [detail, setDetail] = useState(null);
  const [loadingDetail, setLoadingDetail] = useState(true);

  useEffect(() => {
    setLoadingDetail(true);
    api.get(`/api/leagues/${leagueId}/detail`)
      .then((r) => setDetail(r.data.data))
      .catch(() => setDetail(null))
      .finally(() => setLoadingDetail(false));
  }, [leagueId]);

  useEffect(() => {
    setLoading(true);
    setError("");

    getStandings(leagueId, {
      matchday: matchdayParam || undefined,
      group: groupParam || undefined,
    })
      .then((res) => {
        setData(res);
      })
      .catch((err) => {
        console.error(err);
        setError(
          err.message ||
            "Error al cargar la clasificación. Inténtalo de nuevo más tarde."
        );
      })
      .finally(() => {
        setLoading(false);
      });
  }, [leagueId, matchdayParam, groupParam]);

  const handleMatchdayChange = (e) => {
    const value = e.target.value;
    const next = new URLSearchParams(searchParams);

    if (value) {
      next.set("matchday", value);
    } else {
      next.delete("matchday");
    }

    setSearchParams(next);
  };

  if (loading) {
    return <div className="container py-4">Cargando clasificación…</div>;
  }

  if (error) {
    return (
      <div className="container py-4">
        <div className="alert alert-danger">{error}</div>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="container py-4">
        <div className="alert alert-warning">
          No se han encontrado datos de clasificación.
        </div>
      </div>
    );
  }

  const { league, rows, max_matchday, matchday } = data;

  return (
    <div className="container py-4">
      {!loadingDetail && detail && (
        <LeagueHeader detail={detail} active="clasificacion" />
      )}
      <div className="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 className="h4 mb-1">
            Clasificación {league?.name}
            {data.group && ` · ${data.group}`}
          </h2>
          {league?.season && (
            <div className="text-muted small">Temporada {league.season}</div>
          )}
        </div>

        <div className="d-flex align-items-center gap-2">
          {max_matchday ? (
            <>
              <label className="form-label mb-0 me-2 small">
                Hasta jornada
              </label>
              <select
                className="form-select form-select-sm"
                value={matchday || ""}
                onChange={handleMatchdayChange}
              >
                <option value="">Todas</option>
                {Array.from({ length: max_matchday }, (_, i) => i + 1).map(
                  (n) => (
                    <option key={n} value={n}>
                      {n}
                    </option>
                  )
                )}
              </select>
            </>
          ) : (
            <span className="text-muted small">Sin jornadas jugadas aún.</span>
          )}
        </div>
      </div>

      {/* Tabla */}
      {!rows || rows.length === 0 ? (
        <div className="alert alert-info">
          No hay datos de clasificación para esta configuración.
        </div>
      ) : (
        <div className="table-responsive">
          <table className="table table-sm align-middle">
            <thead>
              <tr>
                <th scope="col">Pos</th>
                <th scope="col">Equipo</th>
                <th scope="col" className="text-center">
                  PJ
                </th>
                <th scope="col" className="text-center">
                  PG
                </th>
                <th scope="col" className="text-center">
                  PE
                </th>
                <th scope="col" className="text-center">
                  PP
                </th>
                <th scope="col" className="text-center">
                  GF
                </th>
                <th scope="col" className="text-center">
                  GC
                </th>
                <th scope="col" className="text-center">
                  DG
                </th>
                <th scope="col" className="text-center">
                  Pts
                </th>
                <th scope="col" className="text-center">
                  Racha
                </th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.team_id}>
                  <td>{row.position}</td>
                  <td>{row.team?.name}</td>
                  <td className="text-center">{row.played}</td>
                  <td className="text-center">{row.wins}</td>
                  <td className="text-center">{row.draws}</td>
                  <td className="text-center">{row.losses}</td>
                  <td className="text-center">{row.gf}</td>
                  <td className="text-center">{row.ga}</td>
                  <td className="text-center">{row.gd}</td>
                  <td className="text-center fw-semibold">{row.points}</td>
                  <td className="text-center">
                    <FormBadges form={row.form} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

function FormBadges({ form }) {
  if (!form || !form.length) return <span className="text-muted">—</span>;

  return (
    <span className="d-inline-flex gap-1">
      {form.map((res, idx) => {
        let label = res;
        let cls = "badge bg-secondary";

        if (res === "W") {
          label = "V";
          cls = "badge bg-success";
        } else if (res === "D") {
          label = "E";
          cls = "badge bg-warning text-dark";
        } else if (res === "L") {
          label = "D";
          cls = "badge bg-danger";
        }

        return (
          <span key={idx} className={cls}>
            {label}
          </span>
        );
      })}
    </span>
  );
}
