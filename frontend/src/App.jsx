// src/App.jsx
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { AuthProvider } from "./context/AuthContext";
import Login from "./pages/Login";
import Register from "./pages/Register";
import RequireAuth from "./components/RequireAuth";
import AppLayout from "./layouts/AppLayout";
import Home from "./pages/Home";
import Dashboard from "./pages/Dashboard"
import Forbidden from "./pages/Forbidden";
import RequireRole from "./components/RequireRole";

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          {/* públicas */}
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/forbidden" element={<Forbidden />} />

          {/* privadas */}
          <Route
            element={
              <RequireAuth>
                <AppLayout />
              </RequireAuth>
            }
          >
            {/* Home autenticado */}
            <Route index element={<Home />} />
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
          </Route>

          {/* opcional: 404 */}
          <Route path="*" element={<p>404</p>} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
