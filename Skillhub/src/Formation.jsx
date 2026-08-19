import './Apprenant.css';
import './shared.css';
import Navbar from './Navbar';
import Footer from './footer';
import FormationsCatalogue from './FormationsCatalogue';

// Page publique listant toutes les formations existantes, tous formateurs confondus.
// Accessible sans connexion ; seule l'action "Suivre" nécessite un compte apprenant.
const Formation = () => {
    return (
        <main>
            <div className="Dashboard_Apprenant">
                <Navbar />
                <h1>Toutes les formations</h1>
                <FormationsCatalogue />
                <Footer />
            </div>
        </main>
    );
};

export default Formation;
