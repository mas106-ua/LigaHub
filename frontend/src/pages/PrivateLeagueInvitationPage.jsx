import { useCallback, useEffect, useRef, useState } from "react";
import { Link, useLocation, useNavigate, useParams } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import {
  acceptPrivateLeagueInvitation,
  resolvePrivateLeagueInvitation,
} from "../api/privateLeagueInvitations";

function getMessageFromError(error, fallback) {
  const status = error?.response?.status;

  if (status === 404) {
    return "La invitación no existe, ha caducado o ya no está disponible.";
  }

  if (status === 401) {
    return "Necesitas iniciar sesión para aceptar la invitación.";
  }

  return error?.response?.data?.message || fallback;
}

export default function PrivateLeagueInvitationPage() {
  const { token } = useParams();
  const location = useLocation();
  const navigate = useNavigate();
  const { user, loading: authLoading } = useAuth();

  const [invitation, setInvitation] = useState(null);
  const [loadingInvitation, setLoadingInvitation] = useState(true);
  const [invitationError, setInvitationError] = useState("");

  const [accepting, setAccepting] = useState(false);
  const [acceptError, setAcceptError] = useState("");
  const [acceptResult, setAcceptResult] = useState(null);

  const autoAcceptStartedRef = useRef(false);

  useEffect(() => {
    let cancelled = false;

    async function loadInvitation() {
      setLoadingInvitation(true);
      setInvitationError("");

      try {
        const data = await resolvePrivateLeagueInvitation(token);
        if (!cancelled) {
          setInvitation(data);
        }
      } catch (error) {
        if (!cancelled) {
          setInvitationError(
            getMessageFromError(
              error,
              "No se pudo cargar la invitación de la liga privada."
            )
          );
        }
      } finally {
        if (!cancelled) {
          setLoadingInvitation(false);
        }
      }
    }

    loadInvitation();

    return () => {
      cancelled = true;
    };
  }, [token]);

  const acceptInvitation = useCallback(async () => {
    setAccepting(true);
    setAcceptError("");

    try {
      const data = await acceptPrivateLeagueInvitation(token);
      setAcceptResult(data);
    } catch (error) {
      autoAcceptStartedRef.current = false;
      setAcceptError(
        getMessageFromError(
          error,
          "No se pudo aceptar la invitación. Inténtalo de nuevo."
        )
      );
    } finally {
      setAccepting(false);
    }
  }, [token]);

  useEffect(() => {
    if (authLoading) return;
    if (!user) return;
    if (!invitation) return;
    if (acceptResult) return;
    if (accepting) return;
    if (autoAcceptStartedRef.current) return;

    autoAcceptStartedRef.current = true;
    acceptInvitation();
  }, [authLoading, user, invitation, acceptResult, accepting, acceptInvitation]);

  const league = invitation?.league;
  const redirectTo =
    acceptResult?.redirect_to || (league?.id ? `/mis-ligas/${league.id}` : "/mis-ligas");

  const returnState = { from: location };

  return (
    <div className="container py-4">
      <section
        className="card shadow-sm border-0 app-invite-card"
        aria-labelledby="private-invite-title"
      >
        <div className="app-invite-card__header">
          <span className="app-invite-card__eyebrow">Invitación privada</span>
          <h1 id="private-invite-title" className="app-invite-card__title">
            Únete a una liga privada
          </h1>
          <p className="app-invite-card__subtitle">
            Para acceder a esta liga necesitas iniciar sesión y aceptar la invitación.
          </p>
        </div>

        <div className="card-body p-4" aria-live="polite">
          {loadingInvitation && (
            <div className="d-flex align-items-center gap-2" role="status">
              <div className="spinner-border spinner-border-sm" aria-hidden="true" />
              <span>Cargando invitación…</span>
            </div>
          )}

          {!loadingInvitation && invitationError && (
            <div className="alert alert-danger" role="alert">
              {invitationError}
            </div>
          )}

          {!loadingInvitation && !invitationError && league && (
            <>
              <div className="app-invite-summary">
                <div>
                  <div className="text-muted small">Liga privada</div>
                  <h2 className="h4 mb-1">{league.name}</h2>

                  <div className="app-inline-badges mt-2">
                    {league.season?.code && (
                      <span className="badge bg-light text-dark">
                        Temporada {league.season.code}
                      </span>
                    )}
                    {league.category?.name && (
                      <span className="badge bg-light text-dark">
                        {league.category.name}
                      </span>
                    )}
                    {league.region?.name && (
                      <span className="badge bg-light text-dark">
                        {league.region.name}
                      </span>
                    )}
                  </div>
                </div>
              </div>

              {!authLoading && !user && (
                <div className="app-invite-actions mt-4">
                  <div className="alert alert-info mb-0">
                    Inicia sesión o crea una cuenta para aceptar la invitación.
                  </div>

                  <div className="app-page-actions">
                    <Link
                      to="/login"
                      state={returnState}
                      className="btn btn-primary"
                    >
                      Iniciar sesión
                    </Link>

                    <Link
                      to="/register"
                      state={returnState}
                      className="btn btn-outline-primary"
                    >
                      Crear cuenta
                    </Link>
                  </div>
                </div>
              )}

              {!authLoading && user && accepting && (
                <div className="alert alert-info mt-4 mb-0" role="status">
                  Aceptando invitación…
                </div>
              )}

              {!authLoading && user && acceptError && (
                <div className="app-invite-actions mt-4">
                  <div className="alert alert-danger mb-0" role="alert">
                    {acceptError}
                  </div>

                  <button
                    type="button"
                    className="btn btn-primary"
                    onClick={acceptInvitation}
                    disabled={accepting}
                  >
                    Reintentar
                  </button>
                </div>
              )}

              {!authLoading && user && acceptResult && (
                <div className="app-invite-actions mt-4">
                  <div className="alert alert-success mb-0" role="status">
                    {acceptResult.already_member
                      ? "Ya pertenecías a esta liga privada."
                      : "Invitación aceptada correctamente."}
                  </div>

                  <button
                    type="button"
                    className="btn btn-primary"
                    onClick={() => navigate(redirectTo, { replace: true })}
                  >
                    Ir a la liga
                  </button>

                  <Link to="/mis-ligas" className="btn btn-outline-secondary">
                    Ver mis ligas privadas
                  </Link>
                </div>
              )}
            </>
          )}
        </div>
      </section>
    </div>
  );
}