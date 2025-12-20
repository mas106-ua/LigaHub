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
  if (g === "male")   return "/img/leagues/logo_m.svg";

  return "/img/leagues/logo_m.svg";
};

export default function CompetitionCardWide({ league }) {
  return (
    <div className="card shadow-sm">
      <div className="card-body d-flex flex-column flex-lg-row align-items-start gap-3">
        <img
          src={genderIcon(league)}
          alt="Logo género"
          width={48}
          height={48}
          className="rounded"
        />

        <div className="flex-grow-1">
          <h2 className="h5 mb-1">{league.name}</h2>
          <LeagueMeta league={league} compact />
        </div>

        <div className="ms-lg-auto">
          <Link to={`/comp/${league.id}`} className="btn btn-primary">
            Ver liga
          </Link>
        </div>
      </div>
    </div>
  );
}
