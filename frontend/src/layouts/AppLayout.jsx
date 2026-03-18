import { useAuth } from "../context/AuthContext";
import { Outlet, Link, useLocation, useNavigate } from "react-router-dom";
import { useEffect, useMemo, useRef, useState } from "react";

const API_BASE = import.meta.env.VITE_API_BASE?.replace(/\/$/, "");

function buildNavSections(user, loading) {
  const sections = [
    {
      links: [
        { label: "Inicio", to: "/" },
        { label: "Profesional", to: "/ligas?level=pro" },
        { label: "Semiprofesional", to: "/ligas?level=semi" },
        { label: "Amateur", to: "/ligas?level=amateur" },
      ],
    },
  ];

  if (!loading && user) {
    sections.push({
      links: [
        { label: "Mis ligas", to: "/mis-ligas" },
      ],
    });
  }

  if (!loading && (user?.role === "admin" || user?.role === "superadmin")) {
    sections.push({
      links: [{ label: "Panel admin", to: "/admin/competitions" }],
    });
  }

  if (!loading && user?.role === "superadmin") {
    sections[sections.length - 1].links.push({
      label: "Dashboard",
      to: "/dashboard",
    });
  }

  return sections;
}

function getUserInitial(user) {
  return user?.name?.trim()?.[0]?.toUpperCase() || "U";
}

function normalizePath(path) {
  return path?.replace(/\/$/, "") || "/";
}

export default function AppLayout() {
  const { user, loading, logout } = useAuth();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [userMenuOpen, setUserMenuOpen] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();
  const userMenuRef = useRef(null);

  const mobileMenuId = "app-mobile-navigation";
  const userMenuId = "app-user-menu";

  const navSections = useMemo(
    () => buildNavSections(user, loading),
    [user, loading]
  );

  useEffect(() => {
    setMobileMenuOpen(false);
    setUserMenuOpen(false);
  }, [location.pathname, location.search]);

  useEffect(() => {
    if (!mobileMenuOpen) return undefined;

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";

    return () => {
      document.body.style.overflow = previousOverflow;
    };
  }, [mobileMenuOpen]);

  useEffect(() => {
    const handleKeyDown = (event) => {
      if (event.key === "Escape") {
        setMobileMenuOpen(false);
        setUserMenuOpen(false);
      }
    };

    const handleClickOutside = (event) => {
      if (userMenuRef.current && !userMenuRef.current.contains(event.target)) {
        setUserMenuOpen(false);
      }
    };

    document.addEventListener("keydown", handleKeyDown);
    document.addEventListener("mousedown", handleClickOutside);

    return () => {
      document.removeEventListener("keydown", handleKeyDown);
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, []);

  const handleLogout = async () => {
    setUserMenuOpen(false);
    setMobileMenuOpen(false);
    await logout();
    navigate("/ligas", { replace: true });
  };

  const toggleMobileMenu = () => {
    setUserMenuOpen(false);
    setMobileMenuOpen((current) => !current);
  };

  const toggleUserMenu = () => {
    setMobileMenuOpen(false);
    setUserMenuOpen((current) => !current);
  };

  const isActiveLink = (to) => {
    const [targetPathname, targetSearch = ""] = to.split("?");
    const currentPathname = normalizePath(location.pathname);
    const expectedPathname = normalizePath(targetPathname);

    if (expectedPathname === "/") {
      return currentPathname === "/";
    }

    // Si el link lleva query params, comprobamos que estén presentes,
    // aunque haya más params o el orden sea distinto.
    if (targetSearch) {
      if (
        currentPathname !== expectedPathname &&
        !currentPathname.startsWith(`${expectedPathname}/`)
      ) {
        return false;
      }

      const currentParams = new URLSearchParams(location.search);
      const targetParams = new URLSearchParams(targetSearch);

      for (const [key, value] of targetParams.entries()) {
        if (currentParams.get(key) !== value) {
          return false;
        }
      }

      return true;
    }

    return (
      currentPathname === expectedPathname ||
      currentPathname.startsWith(`${expectedPathname}/`)
    );
  };

  const renderNavLinks = (className = "") =>
  navSections.map((section, index) => (
    <div
      key={section.title || `section-${index}`}
      className={`app-nav__group ${className}`.trim()}
    >
      {section.title ? (
        <span className="app-nav__group-title">{section.title}</span>
      ) : null}

      <div className="app-nav__links">
        {section.links.map((link) => (
          <Link
            key={link.to}
            to={link.to}
            className={`app-nav__link ${
              isActiveLink(link.to) ? "is-active" : ""
            }`.trim()}
          >
            {link.label}
          </Link>
        ))}
      </div>
    </div>
  ));

  const renderUserTrigger = (compact = false) => (
    <button
      type="button"
      onClick={toggleUserMenu}
      className={`app-user-trigger ${
        compact ? "app-user-trigger--compact" : ""
      }`.trim()}
      aria-haspopup="menu"
      aria-expanded={userMenuOpen}
      aria-controls={userMenuId}
    >
      {user?.avatar_url ? (
        <img
          src={
            user.avatar_url.startsWith("http")
              ? user.avatar_url
              : `${API_BASE}${user.avatar_url}`
          }
          alt={user.name}
          className="app-user-trigger__avatar"
        />
      ) : (
        <span
          className="app-user-trigger__avatar app-user-trigger__avatar--fallback"
          aria-hidden="true"
        >
          {getUserInitial(user)}
        </span>
      )}

      {!compact && (
        <>
          <span className="app-user-trigger__text">
            <strong>{user?.name || "Usuario"}</strong>
            <small>
              {user?.role === "superadmin"
                ? "Superadministrador"
                : user?.role === "admin"
                ? "Administrador"
                : "Usuario"}
            </small>
          </span>

          <span className="app-user-trigger__caret" aria-hidden="true">
            ▾
          </span>
        </>
      )}

      {compact && (
        <span className="visually-hidden">Abrir menú de usuario</span>
      )}
    </button>
  );

  return (
    <div className="app-shell">
      <a className="app-skip-link" href="#main-content">
        Saltar al contenido
      </a>

      <header className="app-header">
        <div className="app-header__inner">
          <div className="app-header__left">
            <Link to="/" className="app-brand" title="Inicio">
              <img
                src="/img/logo_rfef.png"
                alt="TFG Fútbol"
                className="app-brand__logo"
              />
              <span className="app-brand__text">
                <strong>TFG Fútbol</strong>
                <small>Competiciones y ligas</small>
              </span>
            </Link>
          </div>

          <nav
            className="app-nav app-nav--desktop"
            aria-label="Navegación principal"
          >
            {renderNavLinks()}
          </nav>

          <div className="app-header__actions">
            {!user ? (
              <div className="app-auth-actions app-auth-actions--desktop">
                <Link to="/login" className="btn btn-light btn-sm">
                  Iniciar sesión
                </Link>
                <Link to="/register" className="btn btn-outline-light btn-sm">
                  Registrarse
                </Link>
              </div>
            ) : (
              <div className="app-user-menu" ref={userMenuRef}>
                <div className="d-none d-md-block">{renderUserTrigger()}</div>
                <div className="d-md-none">{renderUserTrigger(true)}</div>

                {userMenuOpen && (
                  <div
                    id={userMenuId}
                    className="app-user-menu__dropdown shadow-sm"
                    role="menu"
                  >
                    <div className="app-user-menu__summary">
                      <strong>{user?.name || "Usuario"}</strong>
                      <small>{user?.email}</small>
                    </div>

                    <Link
                      to="/profile"
                      className="app-user-menu__item"
                      role="menuitem"
                    >
                      Ver perfil
                    </Link>

                    <Link
                      to="/profile/edit"
                      className="app-user-menu__item"
                      role="menuitem"
                    >
                      Editar perfil
                    </Link>

                    {(user?.role === "admin" || user?.role === "superadmin") && (
                      <Link
                        to="/admin/competitions"
                        className="app-user-menu__item"
                        role="menuitem"
                      >
                        Ir al panel admin
                      </Link>
                    )}

                    <button
                      type="button"
                      onClick={handleLogout}
                      className="app-user-menu__item"
                      role="menuitem"
                    >
                      Cerrar sesión
                    </button>
                  </div>
                )}
              </div>
            )}

            <button
              type="button"
              className="app-mobile-menu-toggle"
              onClick={toggleMobileMenu}
              aria-expanded={mobileMenuOpen}
              aria-controls={mobileMenuId}
              aria-label={
                mobileMenuOpen ? "Cerrar navegación" : "Abrir navegación"
              }
            >
              <span aria-hidden="true">☰</span>
            </button>
          </div>
        </div>
      </header>

      {mobileMenuOpen && (
        <button
          type="button"
          className="app-mobile-nav__backdrop"
          onClick={() => setMobileMenuOpen(false)}
          aria-label="Cerrar navegación"
        />
      )}

      <aside
        id={mobileMenuId}
        className={`app-mobile-nav ${
          mobileMenuOpen ? "is-open" : ""
        }`.trim()}
        aria-label="Navegación móvil"
      >
        <div className="app-mobile-nav__header">
          <div>
            <strong>Menú</strong>
            <small>Accesos principales</small>
          </div>

          <button
            type="button"
            className="app-mobile-nav__close"
            onClick={() => setMobileMenuOpen(false)}
            aria-label="Cerrar navegación"
          >
            ✕
          </button>
        </div>

        <div className="app-mobile-nav__content">
          {renderNavLinks("app-nav__group--mobile")}
        </div>

        <div className="app-mobile-nav__footer">
          {!user ? (
            <div className="app-auth-actions app-auth-actions--mobile">
              <Link to="/login" className="btn btn-light">
                Iniciar sesión
              </Link>
              <Link to="/register" className="btn btn-outline-light">
                Registrarse
              </Link>
            </div>
          ) : (
            <div className="app-mobile-nav__profile-card">
              <div>
                <strong>{user?.name || "Usuario"}</strong>
                <small>{user?.email}</small>
              </div>

              <div className="app-mobile-nav__profile-links">
                <Link to="/profile">Ver perfil</Link>
                <Link to="/profile/edit">Editar perfil</Link>
                <button type="button" onClick={handleLogout}>
                  Cerrar sesión
                </button>
              </div>
            </div>
          )}
        </div>
      </aside>

      <main id="main-content" className="app-main">
        <Outlet />
      </main>
    </div>
  );
}