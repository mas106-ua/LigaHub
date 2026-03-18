import { Link } from "react-router-dom";
import LeagueMeta from "../league/LeagueMeta";

const isYouth = (league) => {
  const txt = `${league?.category?.name || ""} ${league?.name || ""}`.toLowerCase();
  return /juvenil|división de honor juvenil|liga nacional juvenil/.test(txt);
};

const genderIcon = (league) => {
  if (isYouth(league)) return "/img/leagues/logo_j.svg";

  const g = league?.category?.gender;
  if (g === "female") return "/img/leagues/logo_f.svg";
  if (g === "male") return "/img/leagues/logo_m.svg";

  return "/img/leagues/logo_m.svg";
};

export default function CompetitionCardWide({ league }) {
  return (
    <article className="card shadow-sm app-competition-card">
      <div className="card-body app-competition-card__body">
        <img
          src={genderIcon(league)}
          alt="Logo de la competición"
          width={48}
          height={48}
          className="rounded app-competition-card__logo"
        />

        <div className="app-competition-card__content">
          <h2 className="h5 app-competition-card__title">{league.name}</h2>
          <LeagueMeta league={league} compact />
        </div>

        <div className="app-competition-card__actions">
          <Link to={`/comp/${league.id}`} className="btn btn-primary">
            Ver liga
          </Link>
        </div>
      </div>
    </article>
  );
}