// src/App.jsx
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
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
import CompetitionsPage from "./pages/CompetitionsPage";
import MatchdaysPage from "./pages/MatchdaysPage";
import MatchDetailLayout from "./pages/match/MatchDetailLayout";
import OverviewTab from "./pages/tabs/OverviewTab";
import EventsTab from "./pages/tabs/EventsTab";
import LineupsTab from "./pages/tabs/LineupsTab";
import StatsTab from "./pages/tabs/StatsTab";
import AdminMatchdayResultsPage from "./pages/admin/AdminMatchdayResultsPage";
import AdminMatchLineupsPage from "./pages/admin/AdminMatchLineupsPage";


export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route element={<AppLayout />}>
            {/* públicas */}
            {/* Home autenticado */}
            <Route index element={<Home />} />
            <Route path="ligas" element={<CompetitionsPage />} /> {/* alias opcional */}
            <Route path="/comp/:leagueId/jornadas" element={<MatchdaysPage />} />
            <Route path="/partido/:id" element={<MatchDetailLayout />}>
              <Route index element={<Navigate to="eventos" replace />} />
              <Route path="eventos" element={<EventsTab />} />
              <Route path="alineaciones" element={<LineupsTab />} />
              <Route path="estadisticas" element={<StatsTab />} />
            </Route>
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            <Route path="/forbidden" element={<Forbidden />} />

            {/* privadas */}
            <Route element={<RequireAuth />}>
              {/* admin-only */}
              <Route
                path="dashboard"
                element={
                  <RequireRole roles="superadmin">
                    <Dashboard />
                  </RequireRole>
                }
              />

              {/* edición de resultados de una jornada */}
              <Route
                path="admin/ligas/:leagueId/jornadas/:matchdayNumber/resultados"
                element={
                  <RequireRole roles={["admin", "superadmin"]}>
                    <AdminMatchdayResultsPage />
                  </RequireRole>
                }
              />
              <Route
                path="admin/partidos/:matchId/alineaciones"
                element={
                  <RequireRole roles={["admin", "superadmin"]}>
                    <AdminMatchLineupsPage />
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
