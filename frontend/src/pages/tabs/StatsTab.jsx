import { useOutletContext } from "react-router-dom";

export default function StatsTab() {
  const { match } = useOutletContext();
  const rows = Array.isArray(match.team_stats) ? match.team_stats : [];
  if (!rows.length) return <div className="text-muted">No hay estadísticas registradas.</div>;

  const bySide = Object.fromEntries(rows.map(r => [r.side, r]));
  const H = bySide.home || {};
  const A = bySide.away || {};

  // helper para saber si hay datos (no contamos 0 como "sin dato")
  const hasNum = v => typeof v === "number" && !Number.isNaN(v);

  const metrics = [
    { key: "possession",     label: "Posesión",        mode: "joined",  percent: true, suffix: "%" },
    { key: "shots_total",    label: "Disparos totales",mode: "center" },
    { key: "shots_on_target",label: "Tiros a puerta",  mode: "center" },
    { key: "corners",        label: "Córners",         mode: "center" },
    { key: "fouls",          label: "Faltas",          mode: "center" },
    { key: "offsides",       label: "Fueras de juego", mode: "center" },
    { key: "yellow_cards",   label: "Amarillas",       mode: "center" },
    { key: "red_cards",      label: "Rojas",           mode: "center" },
  ]
  // si ambos vienen sin dato -> ocultamos la fila
  .filter(m => hasNum(H[m.key]) || hasNum(A[m.key]));

  return (
    <div className="card">
      <div className="card-body p-0">
        {metrics.map(m => (
          <StatRow
            key={m.key}
            mode={m.mode}
            label={m.label}
            home={num(H[m.key])}
            away={num(A[m.key])}
            percent={!!m.percent}
            suffix={m.suffix}
            highlight={!!m.highlight}
            homeShort={match.home_team.short_name}
            awayShort={match.away_team.short_name}
          />
        ))}
      </div>
    </div>
  );
}

function StatRow({ mode, label, home, away, percent, suffix = "", highlight, homeShort, awayShort }) {
  // porcentajes para reparto
  let shareHome = 0, shareAway = 0;
  if (percent) {
    const total = Math.max(home + away, 1);
    shareHome = clamp((home / total) * 100);
    shareAway = 100 - shareHome;
  } else {
    const total = Math.max(home + away, 0);
    if (total > 0) {
      shareHome = clamp((home / total) * 100);
      shareAway = 100 - shareHome;
    }
  }

  const strongHome = home > away;
  const strongAway = away > home;

  return (
    <div className="stat-row">
      <div className={"stat-left" + (strongHome ? " is-strong" : "")}>
        <div className="stat-value">{fmt(home)}{suffix}</div>
      </div>

      <div className="stat-center">
        <div className="stat-label">{label}</div>

        {mode === "joined" ? (
          <div className={"progress-joined" + (highlight ? " is-highlight" : "")}>
            <div className="fill-home" style={{ width: `${shareHome}%` }} />
            <div className="fill-away" style={{ width: `${shareAway}%` }} />
          </div>
        ) : (
          <div className={"progress-center" + (highlight ? " is-highlight" : "")}>
            {/* desde el centro: cada lado ocupa hasta el 50% del ancho total */}
            <div className="fill-left"  style={{ width: `${shareHome / 2}%` }} />
            <div className="fill-right" style={{ width: `${shareAway / 2}%` }} />
          </div>
        )}

        <div className="stat-teams small text-muted">
          <span>{homeShort}</span><span>{awayShort}</span>
        </div>
      </div>

      <div className={"stat-right" + (strongAway ? " is-strong" : "")}>
        <div className="stat-value">{fmt(away)}{suffix}</div>
      </div>
    </div>
  );
}

const num   = v => (typeof v === "number" && !Number.isNaN(v) ? v : 0);
const fmt   = v => (v ?? "—");
const clamp = x => Math.max(0, Math.min(100, Math.round(x)));
