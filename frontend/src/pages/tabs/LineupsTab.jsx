import { useOutletContext } from "react-router-dom";
import FootballPitch from "../../components/Matchs/FootballPitch";

/* =============== helpers comunes =============== */
const spreadXs = (n) => {
  if (n <= 0) return [];
  const min = 12, max = 88;
  return Array.from({ length: n }, (_, i) => min + ((max - min) * (i + 1)) / (n + 1));
};

const groupByRow = (players = []) => {
  const rowOf = (pos = "") => {
    const t = pos?.toUpperCase?.() || "";
    if (t.includes("GK") || t === "POR") return "GK";
    if (/(RB|LB|RWB|LWB|CB|DF)/.test(t)) return "DEF";
    if (/(DM|MCD)/.test(t)) return "DM";
    if (/(CM|MF|MI|MC)/.test(t)) return "MF";
    if (/(AM|MP)/.test(t)) return "AM";
    if (/(LW|RW|FW|ST|CF|DL)/.test(t)) return "FW";
    return "MF";
  };
  const g = { GK: [], DEF: [], DM: [], MF: [], AM: [], FW: [] };
  players.forEach(p => g[rowOf(p.pos)].push(p));
  return g;
};

const Y_HOME = { GK: 8, DEF: 20, DM: 30, MF: 38, AM: 42, FW: 46 };
const Y_AWAY = { GK: 92, DEF: 80, DM: 70, MF: 62, AM: 60, FW: 54 };

const yFor = (row, side) =>
  side === "home" ? (Y_HOME[row] ?? 0) : (Y_AWAY[row] ?? 100);

/* =============== overlay de jugadores sobre el pitch =============== */
function PitchWithPlayers({
  homeStarters = [],
  awayStarters = [],
  homeTeamName,
  awayTeamName,
  homeCoach,
  awayCoach,
}) {
  const rowsHome = groupByRow(homeStarters);
  const rowsAway = groupByRow(awayStarters);

  return (
    <div className="lp-wrapper">
      <FootballPitch />

      <div className="lp-overlay">
        {/* Etiquetas equipos/entrenadores */}
        <div className="lp-label lp-label--home">
          <span className="lp-label-team">{homeTeamName}</span>
          {homeCoach && <span className="lp-label-coach">{homeCoach}</span>}
        </div>
        <div className="lp-label lp-label--away">
          <span className="lp-label-team">{awayTeamName}</span>
          {awayCoach && <span className="lp-label-coach">{awayCoach}</span>}
        </div>

        {/* HOME (arriba) */}
        {Object.entries(rowsHome).map(([row, players]) => {
          const xs = spreadXs(players.length);
          return players.map((p, i) => {
            const name = p.player_name || "";
            const pos  = p.pos || "";
            return (
              <div
                key={`h-${row}-${i}`}
                className="lp-player lp-player--home"
                style={{ left: `${xs[i]}%`, top: `${yFor(row, "home")}%` }}
                title={`${name}${pos ? ` (${pos})` : ""}`}
              >
                <span className="lp-player__num">{p.shirt ?? "?"}</span>
                {name && (
                  <span className="lp-player__name" title={name}>
                    {name}
                  </span>
                )}
              </div>
            );
          });
        })}

        {/* AWAY (abajo) */}
        {Object.entries(rowsAway).map(([row, players]) => {
          const xs = spreadXs(players.length);
          return players.map((p, i) => {
            const name = p.player_name || "";
            const pos  = p.pos || "";
            return (
              <div
                key={`a-${row}-${i}`}
                className="lp-player lp-player--away"
                style={{ left: `${xs[i]}%`, top: `${yFor(row, "away")}%` }}
                title={`${name}${pos ? ` (${pos})` : ""}`}
              >
                <span className="lp-player__num">{p.shirt ?? "?"}</span>
                {name && (
                  <span className="lp-player__name" title={name}>
                    {name}
                  </span>
                )}
              </div>
            );
          });
        })}
      </div>
    </div>
  );
}

/* =============== banquillo pro =============== */
function BenchItem({ data, align }) {
  const side = align === "right" ? "right" : "left";

  if (!data) {
    // celda vacía (cuando un equipo tiene menos suplentes)
    return <div className={`bench2__cell bench2__cell--${side}`} />;
  }

  const shirt = (data.shirt ?? "•") + "";
  const name  = data.player_name || "Jugador";
  const pos   = data.pos || "";

  return (
    <div className={`bench2__cell bench2__cell--${side}`}>
      <div className={`bench2__item bench2__item--${side}`}>
        <span className="bench2__badge">{shirt}</span>
        <span className="bench2__name" title={name}>{name}</span>
        {pos && <span className="bench2__pos">{pos}</span>}
      </div>
    </div>
  );
}

function BenchTablePro({ home, away }) {
  const left  = home?.bench || [];
  const right = away?.bench || [];
  const max = Math.max(left.length, right.length);

  return (
    <div className="bench2">
      <div className="bench2__title-row">
        <span className="bench2__title">Suplentes</span>
      </div>

      <div className="bench2__body">
        {Array.from({ length: max }).map((_, i) => (
          <div className="bench2__row" key={i}>
            <BenchItem data={left[i]}  align="left" />
            <BenchItem data={right[i]} align="right" />
          </div>
        ))}
      </div>
    </div>
  );
}


/* =============== vista principal =============== */
export default function LineupsTab() {
  const { match } = useOutletContext();
  const lineups = Array.isArray(match?.lineups) ? match.lineups : [];
  const home = lineups.find(l => l.side === "home") || {};
  const away = lineups.find(l => l.side === "away") || {};

  if (!home.starters?.length && !away.starters?.length) {
    return <div className="text-muted">No hay alineaciones disponibles.</div>;
  }

  const homeTeamName =
    match?.home_team?.short_name || match?.home_team?.name || "Local";
  const awayTeamName =
    match?.away_team?.short_name || match?.away_team?.name || "Visitante";

  const homeCoach =
    home.coach_name || home.coach || match?.home_coach || "";
  const awayCoach =
    away.coach_name || away.coach || match?.away_coach || "";

  return (
    <div className="row g-3">
      {/* Campo + jugadores */}
      <div className="col-12 col-xl-7">
        <div className="card h-100">
          <div className="card-body card-body--pitch">
            <PitchWithPlayers
              homeStarters={home.starters || []}
              awayStarters={away.starters || []}
              homeTeamName={homeTeamName}
              awayTeamName={awayTeamName}
              homeCoach={homeCoach}
              awayCoach={awayCoach}
            />
          </div>
        </div>
      </div>

      {/* Banquillo ocupa todo el card */}
      <div className="col-12 col-xl-5">
        <div className="card bench-card">
          <BenchTablePro
            home={{ bench: home.bench || [] }}
            away={{ bench: away.bench || [] }}
          />
        </div>
      </div>

    </div>
  );
}
