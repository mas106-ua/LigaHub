// src/App.jsx
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { AuthProvider } from "./context/AuthContext";
import Login from "./pages/Login";
import Register from "./pages/Register";
import RequireAuth from "./components/RequireAuth";
import AppLayout from "./layouts/AppLayout";
import Home from "./pages/Home";
import Dashboard from "./pages/Dashboard"
import ProfileView from "./pages/ProfileView";
import ProfileEdit from "./pages/ProfileEdit";
import Forbidden from "./pages/Forbidden";
import RequireRole from "./components/RequireRole";
import RequireGuest from "./components/RequireGuest";
import CompetitionsPage from "./pages/CompetitionsPage";

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route element={<AppLayout />}>
            {/* públicas */}
            {/* Home autenticado */}
            <Route index element={<CompetitionsPage />} />
            <Route path="ligas" element={<CompetitionsPage />} /> {/* alias opcional */}
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            <Route path="/forbidden" element={<Forbidden />} />

            {/* privadas */}
            <Route
            >
              {/* admin-only */}
              <Route
                path="dashboard"
                element={
                  <RequireRole roles="superadmin">
                    <Dashboard />
                  </RequireRole>
                }
              />
              {/* más privadas aquí */}
              <Route path="profile" element={<ProfileView />} />
              <Route path="profile/edit" element={<ProfileEdit />} />
            </Route>

            {/* opcional: 404 */}
            <Route path="*" element={<p>404</p>} />
          </Route>
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
