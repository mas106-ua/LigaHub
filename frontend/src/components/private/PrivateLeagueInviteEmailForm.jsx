import { useState } from "react";
import { sendPrivateLeagueInvitationEmail } from "../../api/privateLeagueInvitations";

function getErrorMessage(error) {
  const emailError = error?.response?.data?.errors?.email?.[0];

  if (emailError) return emailError;

  return (
    error?.response?.data?.message ||
    "No se pudo enviar la invitación. Inténtalo de nuevo."
  );
}

export default function PrivateLeagueInviteEmailForm({ leagueId, canManage }) {
  const [email, setEmail] = useState("");
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");
  const [result, setResult] = useState(null);
  const [copied, setCopied] = useState(false);

  if (!canManage) return null;

  const handleSubmit = async (event) => {
    event.preventDefault();

    const normalizedEmail = email.trim();

    setError("");
    setResult(null);
    setCopied(false);

    if (!normalizedEmail) {
      setError("Introduce un correo electrónico.");
      return;
    }

    try {
      setSending(true);

      const data = await sendPrivateLeagueInvitationEmail(
        leagueId,
        normalizedEmail
      );

      setResult(data);
      setEmail("");
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSending(false);
    }
  };

  const handleCopy = async () => {
    if (!result?.invite_url) return;

    try {
      await navigator.clipboard.writeText(result.invite_url);
      setCopied(true);
    } catch {
      setCopied(false);
    }
  };

  return (
    <section
      className="card shadow-sm border-0 app-page-card app-private-invite-email"
      aria-labelledby="private-invite-email-title"
    >
      <div className="card-body">
        <div className="app-page-header mb-3">
          <div>
            <h2 id="private-invite-email-title" className="h5 mb-1">
              Invitar por correo
            </h2>
            <p className="text-muted mb-0">
              Envía un enlace de invitación para que otro usuario pueda unirse a
              esta liga privada.
            </p>
          </div>
        </div>

        {error && (
          <div className="alert alert-danger" role="alert">
            {error}
          </div>
        )}

        {result && (
          <div className="alert alert-success" role="status">
            Invitación enviada correctamente a{" "}
            <strong>{result.email}</strong>.
          </div>
        )}

        <form onSubmit={handleSubmit} noValidate>
          <div className="app-private-invite-email__form">
            <div className="app-private-invite-email__field">
              <label htmlFor="privateInviteEmail" className="form-label">
                Correo del invitado
              </label>
              <input
                id="privateInviteEmail"
                type="email"
                className="form-control"
                placeholder="usuario@correo.com"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                disabled={sending}
                autoComplete="email"
              />
            </div>

            <div className="app-private-invite-email__actions">
              <button
                type="submit"
                className="btn btn-primary"
                disabled={sending}
              >
                {sending ? "Enviando..." : "Enviar invitación"}
              </button>
            </div>
          </div>
        </form>

        {result?.invite_url && (
          <div className="app-private-invite-email__result mt-3">
            <label htmlFor="privateInviteUrl" className="form-label">
              Enlace generado
            </label>

            <div className="input-group">
              <input
                id="privateInviteUrl"
                className="form-control"
                value={result.invite_url}
                readOnly
              />

              <button
                type="button"
                className="btn btn-outline-secondary"
                onClick={handleCopy}
              >
                {copied ? "Copiado" : "Copiar"}
              </button>
            </div>

            <div className="app-page-actions mt-2">
              <a
                href={result.invite_url}
                target="_blank"
                rel="noopener noreferrer"
                className="btn btn-sm btn-outline-primary"
              >
                Abrir enlace de invitación
              </a>
            </div>
          </div>
        )}
      </div>
    </section>
  );
}