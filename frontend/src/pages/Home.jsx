export default function Home() {
  return (
    <div className="bg-white rounded-3 shadow-sm p-4">
      <h1 className="h4 mb-3" style={{ color: "var(--color-primary)" }}>
        Home
      </h1>
      <p className="text-muted mb-0">
        Aquí podrás mostrar un resumen rápido (próximos partidos, ligas, avisos...).
        Ahora mismo no hay datos porque vendrán de la API.
      </p>
    </div>
  );
}
