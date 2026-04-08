import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { getProfile, updateProfile, updatePassword } from "../api/profile";
import { getCurrentUser } from "../api/auth";
import { useAuth } from "../context/AuthContext";

export default function ProfileEdit() {
  const { refreshUser, updateLocalUser } = useAuth();

  const [name, setName] = useState("");
  const [avatarUrl, setAvatarUrl] = useState(null);
  const [avatarFile, setAvatarFile] = useState(null);
  const [savingData, setSavingData] = useState(false);
  const [msgData, setMsgData] = useState(null);

  const [currentPwd, setCurrentPwd] = useState("");
  const [newPwd, setNewPwd] = useState("");
  const [newPwd2, setNewPwd2] = useState("");
  const [savingPwd, setSavingPwd] = useState(false);
  const [msgPwd, setMsgPwd] = useState(null);

  useEffect(() => {
    (async () => {
      try {
        const me = await getProfile();
        setName(me.name || "");

        if (me.avatar_url) {
          const API_BASE = import.meta.env.VITE_API_BASE?.replace(/\/$/, "");
          const full = me.avatar_url.startsWith("http")
            ? me.avatar_url
            : `${API_BASE}${me.avatar_url}`;
          setAvatarUrl(full);
        }
      } catch {
        // ignore
      }
    })();
  }, []);

  function handleAvatarChange(e) {
    const file = e.target.files?.[0];
    setAvatarFile(file || null);
    if (file) setAvatarUrl(URL.createObjectURL(file));
  }

  async function onSubmitData(e) {
    e.preventDefault();
    setMsgData(null);
    setSavingData(true);

    try {
      const { user } = await updateProfile({ name: name.trim(), avatarFile });

      setMsgData({
        type: "success",
        text: "Perfil actualizado correctamente.",
      });

      if (user?.avatar_url) {
        const API_BASE = import.meta.env.VITE_API_BASE?.replace(/\/$/, "");
        const full = user.avatar_url.startsWith("http")
          ? user.avatar_url
          : `${API_BASE}${user.avatar_url}`;
        setAvatarUrl(full);
        await refreshUser?.();
      }

      await getCurrentUser().catch(() => {});

      if (user) {
        updateLocalUser(user);
      } else {
        await refreshUser();
      }
    } catch (err) {
      const msg =
        err?.response?.data?.message || "No se pudo actualizar el perfil.";
      setMsgData({ type: "error", text: msg });
    } finally {
      setSavingData(false);
    }
  }

  async function onSubmitPwd(e) {
    e.preventDefault();
    setMsgPwd(null);
    setSavingPwd(true);

    try {
      await updatePassword({
        current_password: currentPwd,
        password: newPwd,
        password_confirmation: newPwd2,
      });

      setMsgPwd({ type: "success", text: "Contraseña actualizada." });
      setCurrentPwd("");
      setNewPwd("");
      setNewPwd2("");
    } catch (err) {
      const errors = err?.response?.data?.errors;
      let msg =
        err?.response?.data?.message ||
        "No se pudo actualizar la contraseña.";

      if (errors) msg = Object.values(errors).flat().join(" ");
      setMsgPwd({ type: "error", text: msg });
    } finally {
      setSavingPwd(false);
    }
  }

  return (
    <div className="container" style={{ maxWidth: 900 }}>
      <div className="app-page-header mb-4">
        <div>
          <h1 className="app-page-title">Editar perfil</h1>
          <p className="app-page-subtitle">
            Actualiza tus datos personales y tu contraseña.
          </p>
        </div>

        <div className="app-page-actions">
          <Link to="/profile" className="btn btn-outline-secondary">
            Volver
          </Link>
        </div>
      </div>

      <section className="mb-4" aria-labelledby="profile-data-title">
        <div className="card shadow-sm app-accessibility-card">
          <div className="card-body">
            <h2 id="profile-data-title" className="h5 mb-3">
              Datos personales
            </h2>

            {msgData && (
              <div
                className={`alert ${
                  msgData.type === "success" ? "alert-success" : "alert-danger"
                }`}
                role={msgData.type === "success" ? "status" : "alert"}
              >
                {msgData.text}
              </div>
            )}

            <form onSubmit={onSubmitData} className="app-form-stack" noValidate>
              <div className="app-avatar-field">
                <div
                  className="app-avatar-preview"
                  aria-hidden="true"
                  style={{
                    backgroundImage: avatarUrl ? `url(${avatarUrl})` : "none",
                  }}
                />

                <div className="flex-grow-1">
                  <label htmlFor="profile-avatar" className="form-label mb-1">
                    Avatar
                  </label>
                  <input
                    id="profile-avatar"
                    type="file"
                    className="form-control"
                    accept="image/*"
                    onChange={handleAvatarChange}
                  />
                  <div id="profile-avatar-help" className="form-text">
                    JPG, PNG o WebP. Tamaño máximo recomendado: 2 MB.
                  </div>
                </div>
              </div>

              <div>
                <label htmlFor="profile-name" className="form-label">
                  Nombre
                </label>
                <input
                  id="profile-name"
                  className="form-control"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="Tu nombre"
                  autoComplete="name"
                />
              </div>

              <div className="app-form-actions">
                <button
                  type="submit"
                  className="btn btn-primary"
                  disabled={savingData}
                >
                  {savingData ? "Guardando..." : "Guardar cambios"}
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>

      <section aria-labelledby="profile-password-title">
        <div className="card shadow-sm app-accessibility-card">
          <div className="card-body">
            <h2 id="profile-password-title" className="h5 mb-3">
              Contraseña
            </h2>

            {msgPwd && (
              <div
                className={`alert ${
                  msgPwd.type === "success" ? "alert-success" : "alert-danger"
                }`}
                role={msgPwd.type === "success" ? "status" : "alert"}
              >
                {msgPwd.text}
              </div>
            )}

            <form onSubmit={onSubmitPwd} className="app-form-stack" noValidate>
              <div>
                <label htmlFor="current-password" className="form-label">
                  Contraseña actual
                </label>
                <input
                  id="current-password"
                  type="password"
                  className="form-control"
                  value={currentPwd}
                  onChange={(e) => setCurrentPwd(e.target.value)}
                  autoComplete="current-password"
                />
              </div>

              <div>
                <label htmlFor="new-password" className="form-label">
                  Nueva contraseña
                </label>
                <input
                  id="new-password"
                  type="password"
                  className="form-control"
                  value={newPwd}
                  onChange={(e) => setNewPwd(e.target.value)}
                  autoComplete="new-password"
                />
              </div>

              <div>
                <label htmlFor="new-password-confirmation" className="form-label">
                  Confirmar nueva contraseña
                </label>
                <input
                  id="new-password-confirmation"
                  type="password"
                  className="form-control"
                  value={newPwd2}
                  onChange={(e) => setNewPwd2(e.target.value)}
                  autoComplete="new-password"
                />
              </div>

              <div className="app-form-actions">
                <button
                  type="submit"
                  className="btn btn-primary"
                  disabled={savingPwd}
                >
                  {savingPwd ? "Guardando..." : "Actualizar contraseña"}
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>
    </div>
  );
}