import LeagueMeta from "../league/LeagueMeta";

export default function CompetitionCard({ league }) {
  const { name } = league;

  return (
    <div className="card h-100 shadow-sm">
      <div className="card-body">
        <h6 className="card-title mb-2">{name}</h6>
        <LeagueMeta league={league} compact />
      </div>
    </div>
  );
}
