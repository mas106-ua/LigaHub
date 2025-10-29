import { useEffect } from "react";
import api from "./api";
import { getCsrf } from "./csrf";

function App() {
  useEffect(() => {
    const test = async () => {
      try {
        await getCsrf(); // ① pide cookie Sanctum
        const res = await api.get("/api/ping"); // ② hace request con cookies
        console.log("Respuesta backend:", res.data);
      } catch (err) {
        console.error("Error al conectar:", err);
      }
    };
    test();
  }, []);

}

export default App;
