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
import AdminMatchEventsPage from "./pages/admin/AdminMatchEventsPage";
import StandingsPage from "./pages/standings/StandingsPage";
import LeagueStatsPage from "./pages/LeagueStatsPage";
import LeagueDetailPage from "./pages/LeagueDetailPage";
import LeagueTeamsPage from "./pages/LeagueTeamsPage";
import AdminOfficialCompetitionsPage from "./pages/admin/AdminOfficialCompetitionsPage";
import AdminLeagueDashboardPage from "./pages/admin/AdminLeagueDashboardPage";
import MyLeaguesPage from "./pages/MyLeaguesPage";
import MyLeaguesCreatePage from "./pages/MyLeaguesCreatePage";
import MyLeagueDetailPage from "./pages/MyLeagueDetailPage";
import MyLeagueTeamsPage from "./pages/MyLeagueTeamsPage";


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
            <Route path="/comp/:leagueId" element={<LeagueDetailPage />} />
            <Route path="/comp/:leagueId/jornadas" element={<MatchdaysPage />} />
            <Route path="/comp/:leagueId/clasificacion" element={<StandingsPage />} />
            <Route path="/comp/:leagueId/estadisticas" element={<LeagueStatsPage />} />
            <Route path="/comp/:leagueId/equipos" element={<LeagueTeamsPage />} />
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
              <Route
                path="admin/partidos/:matchId/eventos"
                element={
                  <RequireRole roles={["admin", "superadmin"]}>
                    <AdminMatchEventsPage />
                  </RequireRole>
                }
              />
              {/* más privadas aquí */}
              <Route path="profile" element={<ProfileView />} />
              <Route path="profile/edit" element={<ProfileEdit />} />

              <Route
                path="admin/competitions"
                element={
                  <RequireRole roles={["admin", "superadmin"]}>
                    <AdminOfficialCompetitionsPage />
                  </RequireRole>
                }
              />

              <Route
                path="admin/ligas/:leagueId"
                element={
                  <RequireRole roles={["admin", "superadmin"]}>
                    <AdminLeagueDashboardPage />
                  </RequireRole>
                }
              />

              <Route path="mis-ligas" element={<MyLeaguesPage />} />
              <Route path="mis-ligas/crear" element={<MyLeaguesCreatePage />} />
              <Route path="mis-ligas/:leagueId" element={<MyLeagueDetailPage />} />
              <Route path="mis-ligas/:leagueId/equipos" element={<MyLeagueTeamsPage />} />
            </Route>

            {/* opcional: 404 */}
            <Route path="*" element={<p>404</p>} />
          </Route>
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
