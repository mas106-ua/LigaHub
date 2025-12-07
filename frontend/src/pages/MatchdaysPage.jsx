import { useEffect, useMemo, useState } from "react";
import { useParams, useSearchParams } from "react-router-dom";
import {
  fetchMatchdays,
  fetchMatchesByMatchday,
  fetchLeagueGroups,
  fetchLeagueSiblings,
} from "../api/matchdays";
import api from "../api/api";
import MatchRow from "../components/Matchs/MatchRow";
import SiblingsTabs from "../components/Matchs/SiblingsTabs";
import LeagueHeader from "../components/league/LeagueHeader";
import LeagueSeasonSwitcher from "../components/league/LeagueSeasonSwitcher";

export default function MatchdaysPage() {
  const { leagueId } = useParams();
  const [sp, setSp] = useSearchParams();

  // query params que pide el enunciado
  const qMatchday = sp.get("matchday");
  const qGroup    = sp.get("group") || "";

  // estado
  const [loading, setLoading] = useState(true);
  const [err, setErr]         = useState("");
  const [matchdays, setMatchdays] = useState([]);
  const [matches, setMatches] = useState([]);
  const [groups, setGroups]   = useState([]);     // strings
  const [siblings, setSiblings] = useState([]);   // [{id,name,groupNumber?}]

  const [detail, setDetail] = useState(null);
  const [loadingDetail, setLoadingDetail] = useState(true);

  // carga del detalle SOLO para el header
  useEffect(() => {
    setLoadingDetail(true);
    api.get(`/api/leagues/${leagueId}/detail`)
      .then((r) => setDetail(r.data.data))
      .catch(() => setDetail(null))
      .finally(() => setLoadingDetail(false));
  }, [leagueId]);

  const currentMatchday = useMemo(() => {
    const n = parseInt(qMatchday, 10);
    return Number.isFinite(n) ? n : undefined;
  }, [qMatchday]);

  // cargar siblings + groups + matchdays al entrar/cambiar liga
  useEffect(() => {
    let cancel = false;
    (async () => {
      try {
        setLoading(true); setErr("");
        const [sib, gs, md] = await Promise.all([
          fetchLeagueSiblings(leagueId),
          fetchLeagueGroups(leagueId),
          fetchMatchdays(leagueId, qGroup || undefined),
        ]);
        if (cancel) return;
        setSiblings(sib);
        setGroups(gs);
        setMatchdays(md);

        // si no hay matchday en URL, selecciona el 1º
        if (!qMatchday && md.length) {
          const first = md[0].number;
          sp.set("matchday", String(first));
          if (qGroup) sp.set("group", qGroup); else sp.delete("group");
          setSp(sp, { replace: true });
        }
      } catch (e) {
        if (!cancel) setErr(e?.message || "Error cargando jornadas");
      } finally {
        if (!cancel) setLoading(false);
      }
    })();
    return () => { cancel = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leagueId]);

  // cargar partidos al cambiar matchday o group
  useEffect(() => {
    let cancel = false;
    (async () => {
      if (!currentMatchday) return;
      try {
        setLoading(true); setErr("");
        const data = await fetchMatchesByMatchday(leagueId, currentMatchday, qGroup || undefined);
        if (!cancel) setMatches(data);
      } catch (e) {
        if (!cancel) setErr(e?.message || "Error cargando partidos");
      } finally {
        if (!cancel) setLoading(false);
      }
    })();
    return () => { cancel = true; };
  }, [leagueId, currentMatchday, qGroup]);

  // opciones de selects
  const matchdayOptions = useMemo(
    () => matchdays.map((md) => ({ value: md.number, label: `Jornada ${md.number}` })),
    [matchdays]
  );

  // handlers
  const onChangeMatchday = (value) => {
    if (!value) return;
    sp.set("matchday", String(value));
    if (qGroup) sp.set("group", qGroup); else sp.delete("group");
    setSp(sp);
  };
  const onChangeGroup = (value) => {
    if (value) sp.set("group", value); else sp.delete("group");
    // al cambiar grupo, conviene mantener matchday si ya hay uno;
    // si no hay, se seleccionará en el efecto anterior
    setSp(sp);
  };

  return (
    <div className="container py-4">
      {!loadingDetail && detail && (
        <>
          <LeagueHeader detail={detail} active="jornadas" />
          <LeagueSeasonSwitcher />
        </>
      )}
      <div className="d-flex align-items-center justify-content-between mb-3">
        <h1 className="h4 m-0">Jornadas</h1>
      </div>

      {/* Tabs para G1/G2/... (siblings) */}
      <SiblingsTabs currentId={leagueId} siblings={siblings} />

      {/* Filtros */}
      <div className="row g-3 mb-3">
        <div className="col-12 col-md-4">
          <label className="form-label small text-uppercase text-muted">Jornada</label>
          <select
            className="form-select"
            value={currentMatchday ?? ""}
            onChange={(e) => onChangeMatchday(e.target.value)}
          >
            {!matchdayOptions.length && <option value="">Sin jornadas</option>}
            {matchdayOptions.map((opt) => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
        </div>

        {Array.isArray(groups) && groups.length > 1 && (
          <div className="col-12 col-md-4">
            <label className="form-label small text-uppercase text-muted">Grupo</label>
            <select
              className="form-select"
              value={qGroup}
              onChange={(e) => onChangeGroup(e.target.value)}
            >
              <option value="">Todos</option>
              {groups.map((g) => (
                <option key={g} value={g}>{g}</option>
              ))}
            </select>
          </div>
        )}
      </div>

      {/* Cuerpo */}
      {err && <div className="alert alert-danger">{err}</div>}

      {loading && (
        <div className="d-flex align-items-center gap-2">
          <div className="spinner-border spinner-border-sm" role="status" />
          <span>Cargando…</span>
        </div>
      )}

      {!loading && !err && matches.length === 0 && (
        <div className="text-muted">No hay partidos para los filtros seleccionados.</div>
      )}

      {!loading && !err && matches.length > 0 && (
        <ul className="list-group">
          {matches.map((m) => <MatchRow key={m.id} m={m} />)}
        </ul>
      )}
    </div>
  );
}
