import { useEffect, useState } from "react";
import { Button, Spinner, Badge } from "react-bootstrap";
import { checkPrivateMatchReport, downloadPrivateMatchReport, generatePrivateMatchReport } from "../api/privateMatchReport";

export default function MatchReportActions({ matchId, canManage, matchStatus }) {
  const [state, setState] = useState("checking"); // checking | none | available | generating
  const [error, setError] = useState("");

  useEffect(() => {
    let mounted = true;

    const check = async () => {
      setError("");
      setState("checking");
      try {
        const exists = await checkPrivateMatchReport(matchId);
        if (!mounted) return;
        setState(exists ? "available" : "none");
      } catch {
        if (!mounted) return;
        setState("none");
      }
    };

    check();
    return () => { mounted = false; };
  }, [matchId]);

  const handleDownload = async () => {
    setError("");
    try {
      const res = await downloadPrivateMatchReport(matchId);
      const blob = new Blob([res.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);

      const a = document.createElement("a");
      a.href = url;
      a.download = `acta_partido_${matchId}.pdf`;
      a.click();

      window.URL.revokeObjectURL(url);
    } catch {
      setError("No se pudo descargar.");
    }
  };

  const handleGenerate = async () => {
    setError("");
    setState("generating");
    try {
      await generatePrivateMatchReport(matchId);
      setState("available");
    } catch (e) {
      const status = e?.response?.status;
      if (status === 403) setError("Sin permisos.");
      else if (status === 422) setError("Solo partidos jugados.");
      else setError("No se pudo generar.");
      setState("none");
    }
  };

  if (state === "checking") return <Spinner animation="border" size="sm" />;

  const canGenerate = canManage && matchStatus === "played";

  return (
    <div className="d-flex justify-content-end gap-2 align-items-center flex-wrap">
      {state === "available" ? (
        <Button size="sm" variant="success" onClick={handleDownload}>
          Descargar
        </Button>
      ) : (
        <Badge bg="light" text="dark">Sin acta</Badge>
      )}

      {canGenerate && (
        <Button
          size="sm"
          variant="outline-primary"
          onClick={handleGenerate}
          disabled={state === "generating"}
        >
          {state === "generating" ? "Generando…" : "Generar"}
        </Button>
      )}

      {error && <span style={{ fontSize: 12, color: "#b00020" }}>{error}</span>}
    </div>
  );
}
