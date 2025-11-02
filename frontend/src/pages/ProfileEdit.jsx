// src/pages/ProfileEdit.jsx
import { useEffect, useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { getProfile, updateProfile, updatePassword } from "../api/profile";
import { getCurrentUser } from "../api/auth";
import { useAuth } from "../context/AuthContext";

export default function ProfileEdit() {
  const navigate = useNavigate();
  const { refreshUser, updateLocalUser } = useAuth();

  // Datos
  const [name, setName] = useState("");
  const [avatarUrl, setAvatarUrl] = useState(null);
  const [avatarFile, setAvatarFile] = useState(null);
  const [savingData, setSavingData] = useState(false);
  const [msgData, setMsgData] = useState(null);

  // Password
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
      setMsgData({ type: "success", text: "Perfil actualizado correctamente." });
      if (user?.avatar_url) {
        const API_BASE = import.meta.env.VITE_API_BASE?.replace(/\/$/, "");
        const full = user.avatar_url.startsWith("http")
            ? user.avatar_url
            : `${API_BASE}${user.avatar_url}`;
        setAvatarUrl(full);
        // y refrescamos el contexto:
        await refreshUser?.(); // si lo expusiste en el contexto
        }
      await getCurrentUser().catch(() => {});
        if (user) {
        updateLocalUser(user);         // ya tenemos el user actualizado
            } else {
        await refreshUser();           // por si la API no devolviera el objeto
            }
    } catch (err) {
      const msg = err?.response?.data?.message || "No se pudo actualizar el perfil.";
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
      setCurrentPwd(""); setNewPwd(""); setNewPwd2("");
    } catch (err) {
      const errors = err?.response?.data?.errors;
      let msg = err?.response?.data?.message || "No se pudo actualizar la contraseña.";
      if (errors) msg = Object.values(errors).flat().join(" ");
      setMsgPwd({ type: "error", text: msg });
    } finally {
      setSavingPwd(false);
    }
  }

  return (
    <div className="container" style={{ maxWidth: 900 }}>
      <div className="d-flex align-items-center justify-content-between mb-4">
        <h2 className="m-0">Editar perfil</h2>
        <Link to="/profile" className="btn btn-outline-secondary">Volver</Link>
      </div>

      {/* Datos */}
      <section className="mb-5">
        <div className="card shadow-sm">
          <div className="card-body">
            <h5 className="card-title mb-3">Datos</h5>

            {msgData && (
              <div className={`alert ${msgData.type === "success" ? "alert-success" : "alert-danger"}`}>
                {msgData.text}
              </div>
            )}

            <form onSubmit={onSubmitData} className="d-grid gap-3">
              <div className="d-flex align-items-center gap-3">
                <div
                  style={{
                    width: 64, height: 64, borderRadius: "50%",
                    backgroundColor: "#f1f1f1",
                    backgroundImage: avatarUrl ? `url(${avatarUrl})` : "none",
                    backgroundSize: "cover", backgroundPosition: "center",
                  }}
                />
                <div>
                  <label className="form-label mb-1">Avatar</label>
                  <input type="file" className="form-control" accept="image/*" onChange={handleAvatarChange} />
                  <div className="form-text">JPG/PNG/WebP. Máx. 2 MB.</div>
                </div>
              </div>

              <div>
                <label className="form-label">Nombre</label>
                <input
                  className="form-control"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="Tu nombre"
                />
              </div>

              <button type="submit" className="btn btn-danger" disabled={savingData}>
                {savingData ? "Guardando..." : "Guardar cambios"}
              </button>
            </form>
          </div>
        </div>
      </section>

      {/* Contraseña */}
      <section>
        <div className="card shadow-sm">
          <div className="card-body">
            <h5 className="card-title mb-3">Contraseña</h5>

            {msgPwd && (
              <div className={`alert ${msgPwd.type === "success" ? "alert-success" : "alert-danger"}`}>
                {msgPwd.text}
              </div>
            )}

            <form onSubmit={onSubmitPwd} className="d-grid gap-3">
              <div>
                <label className="form-label">Contraseña actual</label>
                <input type="password" className="form-control"
                  value={currentPwd} onChange={(e) => setCurrentPwd(e.target.value)} autoComplete="current-password" />
              </div>
              <div>
                <label className="form-label">Nueva contraseña</label>
                <input type="password" className="form-control"
                  value={newPwd} onChange={(e) => setNewPwd(e.target.value)} autoComplete="new-password" />
              </div>
              <div>
                <label className="form-label">Confirmar nueva contraseña</label>
                <input type="password" className="form-control"
                  value={newPwd2} onChange={(e) => setNewPwd2(e.target.value)} autoComplete="new-password" />
              </div>

              <button type="submit" className="btn btn-danger" disabled={savingPwd}>
                {savingPwd ? "Guardando..." : "Actualizar contraseña"}
              </button>
            </form>
          </div>
        </div>
      </section>
    </div>
  );
}
