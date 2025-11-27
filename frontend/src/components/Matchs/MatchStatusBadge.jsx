export default function MatchStatusBadge({ status }) {
  const map = {
    played:    { txt: "Jugado",    cls: "bg-success" },
    scheduled: { txt: "Pendiente", cls: "bg-secondary" },
    postponed: { txt: "Aplazado",  cls: "bg-warning text-dark" },
    canceled:  { txt: "Cancelado", cls: "bg-danger" },
  };
  const meta = map[status] || { txt: status || "—", cls: "bg-secondary" };
  return <span className={`badge ${meta.cls}`}>{meta.txt}</span>;
}
