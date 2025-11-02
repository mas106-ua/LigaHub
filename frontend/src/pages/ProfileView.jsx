// src/pages/ProfileView.jsx
import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { getProfile } from "../api/profile";

export default function ProfileView() {
  const [me, setMe] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      try {
        const data = await getProfile(); // { id, name, email, role, avatar_url }
        const API_BASE = import.meta.env.VITE_API_BASE?.replace(/\/$/, "");
        if (data.avatar_url) {
        data.avatar_url = data.avatar_url.startsWith("http")
            ? data.avatar_url
            : `${API_BASE}${data.avatar_url}`;
        }
        setMe(data);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  if (loading) return <p>Cargando…</p>;
  if (!me) return <p>No se pudo cargar el perfil.</p>;

  return (
    <div className="container" style={{ maxWidth: 900 }}>
      <div className="d-flex align-items-center justify-content-between mb-4">
        <h2 className="m-0">Mi perfil</h2>
        <Link to="/profile/edit" className="btn btn-danger">Editar perfil</Link>
      </div>

      <div className="card shadow-sm">
        <div className="card-body d-flex gap-4">
          <div
            style={{
              width: 96,
              height: 96,
              borderRadius: "50%",
              backgroundColor: "#f1f1f1",
              backgroundImage: me.avatar_url ? `url(${me.avatar_url})` : "none",
              backgroundSize: "cover",
              backgroundPosition: "center",
              flex: "0 0 auto",
            }}
          />
          <div className="flex-grow-1">
            <div className="mb-2"><strong>Nombre:</strong> {me.name}</div>
            <div className="mb-2"><strong>Email:</strong> {me.email}</div>
            <div className="mb-2">
              <strong>Rol:</strong>{" "}
              <span className="badge text-bg-secondary">{me.role}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
