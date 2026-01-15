import { Card, Button } from "react-bootstrap";
import { Link, useParams } from "react-router-dom";

export default function MyLeagueDetailPlaceholderPage() {
  const { leagueId } = useParams();

  return (
    <div className="d-flex justify-content-center">
      <Card className="shadow-sm border-0" style={{ maxWidth: 720, width: "100%", borderRadius: "1rem" }}>
        <Card.Body className="p-4">
          <h4 className="mb-1">Detalle de liga</h4>
          <div className="text-muted">Liga #{leagueId}</div>
          <hr />
          <div className="text-muted">Detalle pendiente (FE-03 / BE-04).</div>

          <div className="mt-3">
            <Button as={Link} to="/mis-ligas" variant="secondary">
              Volver
            </Button>
          </div>
        </Card.Body>
      </Card>
    </div>
  );
}
