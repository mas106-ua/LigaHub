// src/App.jsx
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { AuthProvider } from "./context/AuthContext";
import Login from "./pages/Login";
// tu register ya lo tienes
import Register from "./pages/Register";
import RequireAuth from "./components/RequireAuth";
import AppLayout from "./layouts/AppLayout";

function HomePage() {
  return <h1 className="text-2xl font-bold">Home privada ✅</h1>;
}

function DashboardPage() {
  return <h1 className="text-2xl font-bold">Dashboard</h1>;
}

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          {/* públicas */}
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />

          {/* privadas */}
          <Route
            element={
              <RequireAuth>
                <AppLayout />
              </RequireAuth>
            }
          >
            <Route path="/" element={<HomePage />} />
            <Route path="/dashboard" element={<DashboardPage />} />
            {/* más privadas aquí */}
          </Route>

          {/* opcional: 404 */}
          <Route path="*" element={<p>404</p>} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
