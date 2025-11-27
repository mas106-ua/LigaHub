import { useEffect, useMemo, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { fetchLeagueDetail } from "../api/leagueDetail";
import LeagueTabs from "./LeagueTabs";
import LeagueHeader from "../components/league/LeagueHeader";

function getErrorMessage(error) {
  if (!error) return "Error desconocido";
  const res = error.response;
  if (res?.data?.message) return res.data.message;
  return error.message || "Error de red";
}

export default function LeagueDetailPage() {
  const { leagueId } = useParams();

  const [detail, setDetail] = useState(null);
  const [loading, setLoading] = useState(true);
  const [errorMsg, setErrorMsg] = useState(null);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setErrorMsg(null);

    fetchLeagueDetail(leagueId)
      .then((data) => {
        if (cancelled) return;
        setDetail(data);
      })
      .catch((err) => {
        if (cancelled) return;
        setErrorMsg(getErrorMessage(err));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [leagueId]);

  const groupedTeams = useMemo(() => {
    if (!detail?.teams) return [];
    const map = new Map();

    detail.teams.forEach((t) => {
      const key = t.group || null;
      if (!map.has(key)) map.set(key, []);
      map.get(key).push(t);
    });

    // ordenar equipos dentro de cada grupo por nombre
    map.forEach((arr) => {
      arr.sort((a, b) => a.name.localeCompare(b.name, "es"));
    });

    // devolvemos [{ key, name, teams }]
    const groups = detail.groups && detail.groups.length
      ? detail.groups.map((g) => ({
          key: g.key || null,
          name: g.name || "Único",
          teams: map.get(g.key || null) || [],
        }))
      : [
          {
            key: null,
            name: "Único",
            teams: map.get(null) || [],
          },
        ];

    return groups;
  }, [detail]);

  if (loading) {
    return (
      <div className="container py-4">
        <h3>Detalle de liga</h3>
        <p>Cargando datos de la liga...</p>
      </div>
    );
  }

  if (errorMsg) {
    return (
      <div className="container py-4">
        <h3>Detalle de liga</h3>
        <div className="alert alert-danger">{errorMsg}</div>
      </div>
    );
  }

  if (!detail) {
    return (
      <div className="container py-4">
        <h3>Detalle de liga</h3>
        <p>No se han encontrado datos para esta liga.</p>
      </div>
    );
  }

  const { name, season, category, region, matchdays, features } = detail;

  return (
    <div className="container py-4">
      <LeagueHeader detail={detail} active="resumen" />

      <div className="row">
        {/* Columna izquierda: resumen */}
        <div className="col-lg-6">
          <section className="card mb-3">
            <div className="card-header">
              <h5 className="m-0">Resumen de la liga</h5>
            </div>
            <div className="card-body">
              <dl className="row mb-0">
                <dt className="col-sm-4">Temporada</dt>
                <dd className="col-sm-8">{season?.code || "—"}</dd>

                <dt className="col-sm-4">Categoría</dt>
                <dd className="col-sm-8">{category?.name || "—"}</dd>

                <dt className="col-sm-4">Región</dt>
                <dd className="col-sm-8">{region?.name || "—"}</dd>

                <dt className="col-sm-4">Jornadas</dt>
                <dd className="col-sm-8">
                  {matchdays?.min != null && matchdays?.max != null
                    ? `${matchdays.min} - ${matchdays.max}`
                    : "—"}
                </dd>

                <dt className="col-sm-4">Partidos</dt>
                <dd className="col-sm-8">
                  {matchdays?.total_matches ?? 0} totales
                  {", "}
                  {matchdays?.played_matches ?? 0} jugados
                </dd>
              </dl>
            </div>
          </section>

          <section className="card mb-3">
            <div className="card-header">
              <h5 className="m-0">Accesos rápidos</h5>
            </div>
            <div className="card-body">
              <div className="d-flex flex-wrap gap-2">
                {features?.has_matchdays && (
                  <Link
                    to={`/comp/${leagueId}/jornadas`}
                    className="btn btn-sm btn-outline-primary"
                  >
                    Ver jornadas
                  </Link>
                )}
                {features?.has_standings && (
                  <Link
                    to={`/comp/${leagueId}/clasificacion`}
                    className="btn btn-sm btn-outline-primary"
                  >
                    Ver clasificación
                  </Link>
                )}
                {features?.has_stats && (
                  <Link
                    to={`/comp/${leagueId}/estadisticas`}
                    className="btn btn-sm btn-outline-primary"
                  >
                    Ver estadísticas
                  </Link>
                )}
              </div>

              <div className="mt-3">
                <span className="badge bg-light text-dark me-1">
                  Jornadas: {features?.has_matchdays ? "sí" : "no"}
                </span>
                <span className="badge bg-light text-dark me-1">
                  Clasificación: {features?.has_standings ? "sí" : "no"}
                </span>
                <span className="badge bg-light text-dark">
                  Stats jugadores: {features?.has_stats ? "sí" : "no"}
                </span>
              </div>
            </div>
          </section>
        </div>

        {/* Columna derecha: equipos */}
        <div className="col-lg-6" id="equipos">
          <section className="card mb-3">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="m-0">Equipos participantes</h5>
            </div>
            <div className="card-body p-0">
              {groupedTeams.length === 0 ? (
                <div className="p-3 text-muted">
                  No se han encontrado equipos para esta liga.
                </div>
              ) : (
                groupedTeams.map((grp, idx) => (
                  <div key={`grp-${grp.key ?? "default"}-${idx}`}>
                    {groupedTeams.length > 1 && (
                      <div className="px-3 pt-3 pb-1 fw-semibold text-muted small">
                        Grupo {grp.name}
                      </div>
                    )}
                    {grp.teams.length === 0 ? (
                      <div className="px-3 pb-2 text-muted small">
                        No hay equipos en este grupo.
                      </div>
                    ) : (
                      <div className="table-responsive">
                        <table className="table table-sm mb-0">
                          <tbody>
                            {grp.teams.map((t) => (
                              <tr key={t.id}>
                                <td>{t.name}</td>
                                <td className="text-muted">
                                  {t.short_name && t.short_name !== t.name
                                    ? t.short_name
                                    : ""}
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                    {idx < groupedTeams.length - 1 && <hr className="m-0" />}
                  </div>
                ))
              )}
            </div>
          </section>
        </div>
      </div>
    </div>
  );
}
