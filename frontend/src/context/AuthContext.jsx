// src/context/AuthContext.jsx
import {
  loginUser,
  getCurrentUser,
  logoutUser,
} from "../api/auth"; // <- las de arriba

import {
  createContext,
  useContext,
  useEffect,
  useState,
  useCallback,
} from "react";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true); // carga inicial
  const [authLoading, setAuthLoading] = useState(false); // login/logout

  // cargar usuario al montar
  useEffect(() => {
    let mounted = true;

    async function load() {
      try {
        const u = await getCurrentUser();
        if (mounted) setUser(u);
      } catch (e) {
        if (mounted) setUser(null);
      } finally {
        if (mounted) setLoading(false);
      }
    }

    load();

    return () => {
      mounted = false;
    };
  }, []);

  const refreshUser = useCallback(async () => {
    try {
      const u = await getCurrentUser();
      setUser(u);
      return u;
    } catch (e) {
      return null;
    }
  }, []);

  // actualiza solo en memoria (cuando ya tienes el user devuelto por la API)
  const updateLocalUser = useCallback((partial) => {
    setUser((prev) => (prev ? { ...prev, ...partial } : prev));
  }, []);

  const login = useCallback(async ({ email, password }) => {
    setAuthLoading(true);
    try {
      await loginUser({ email, password });
      // refresca el usuario para incluir su rol
      const u = await getCurrentUser();
      setUser(u);
      return { ok: true };
    } catch (error) {
      // ...
    } finally {
      setAuthLoading(false);
    }
  }, []);

  const logout = useCallback(async () => {
    setAuthLoading(true);
    try {
      await logoutUser();
    } catch (e) {
      // aunque falle, limpiamos
    } finally {
      setUser(null);
      setAuthLoading(false);
    }
  }, []);

  return (
    <AuthContext.Provider
      value={{
        user,
        isAuth: !!user,
        loading,
        authLoading,
        login,
        logout,
        refreshUser,
        updateLocalUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth debe usarse dentro de <AuthProvider>");
  }
  return ctx;
}
