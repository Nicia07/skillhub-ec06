import Apprenant from "./Apprenant";
import Formateur from "./Formateur";
import Formation from "./Formation";
import FormationDetail from "./FormationDetail";
import { Routes, Route, Navigate } from "react-router-dom";

function App() {

  return (
    <Routes>
      <Route path="/" element={<Formateur />} />
      <Route path="/Apprenant" element={<Apprenant />} />
      <Route path="/Formation" element={<Formation />} />
      <Route path="/Formation/:id" element={<FormationDetail />} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default App;
