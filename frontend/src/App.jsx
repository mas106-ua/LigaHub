// src/App.jsx
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { AuthProvider } from "./context/AuthContext";
import Login from "./pages/Login";
import Register from "./pages/Register";
import RequireAuth from "./components/RequireAuth";
import AppLayout from "./layouts/AppLayout";
import Home from "./pages/Home";
import Dashboard from "./pages/Dashboard"

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
            <Route path="/" element={<Home />} />
            <Route path="/dashboard" element={<Dashboard />} />
            {/* más privadas aquí */}
          </Route>

          {/* opcional: 404 */}
          <Route path="*" element={<p>404</p>} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
