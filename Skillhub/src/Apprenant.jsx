import React, { useState, useEffect } from 'react';
import './Apprenant.css';
import './shared.css';
import Navbar from './Navbar';
import Footer from './footer';
import FormationCard from './FormationCard';
import FormationsCatalogue from './FormationsCatalogue';
import api from './api';
import { useAuth } from './AuthContext';
import { levelLabel, durationLabel } from './formationDisplay';
import photo_2 from './assets/profil.webp';

const Apprenant = () => {
    const { user, isLoggedIn, loading: authLoading } = useAuth();
    const isApprenant = isLoggedIn && user?.role === 'apprenant';

    const [inscriptions, setInscriptions] = useState([]);
    const [loadingInscriptions, setLoadingInscriptions] = useState(false);
    const [actionError, setActionError] = useState("");

    const fetchInscriptions = async () => {
        try {
            setLoadingInscriptions(true);
            const res = await api.get('/mes-inscriptions');
            setInscriptions(res.data);
        } catch (error) {
            console.error("Erreur inscriptions:", error.response ? error.response.status : error.message);
        } finally {
            setLoadingInscriptions(false);
        }
    };

    useEffect(() => {
        if (isApprenant) {
            fetchInscriptions();
        } else {
            setInscriptions([]);
        }
    }, [isApprenant]);

    const terminer = async (idInscription) => {
        setActionError("");
        try {
            await api.put(`/inscriptions/${idInscription}/terminer`);
            await fetchInscriptions();
        } catch (error) {
            setActionError(error.response?.data?.message || "Erreur lors de la validation.");
        }
    };

    const seDesinscrire = async (idInscription) => {
        if (!window.confirm("Voulez-vous vraiment arrêter de suivre cette formation ?")) return;
        setActionError("");
        try {
            await api.delete(`/inscriptions/${idInscription}`);
            await fetchInscriptions();
        } catch (error) {
            setActionError(error.response?.data?.message || "Erreur lors de la désinscription.");
        }
    };

    const suivies = inscriptions.filter(i => i.status === 'en cours');
    const terminees = inscriptions.filter(i => i.status === 'terminée');

    if (authLoading) {
        return null;
    }

    return (
        <main>
            <div className="Dashboard_Apprenant">
                <Navbar />

                <h1>Catalogue des formations</h1>

                <FormationsCatalogue onChange={fetchInscriptions} />

                {actionError && <p className="error-text center">{actionError}</p>}

                {isApprenant && (
                    <div className='pageApprenant'>
                        <div className='Apprenant-cards'>
                            {/* --- SECTION FORMATIONS SUIVIES --- */}
                            <div className='FormationSuivie'>
                                <h2>Mes formations suivies:</h2>
                                <div className="Cards_FormationSuivie">
                                    {loadingInscriptions ? <p>Chargement...</p> : suivies.length === 0 ? <p>Vous ne suivez aucune formation pour le moment.</p> : (
                                        suivies.map((ins) => (
                                            <div className="cardA" key={ins.id}>
                                                <FormationCard
                                                    photo={ins.formation.photo || photo_2}
                                                    altText={ins.formation.title}
                                                    title={ins.formation.title}
                                                    name={ins.formation.pseudo_formateur || "Formateur"}
                                                    categorie={ins.formation.categorie}
                                                    niveau={levelLabel(ins.formation.level)}
                                                    temps={durationLabel(ins.formation.duration)}
                                                    ville={ins.formation.ville}
                                                    prix={ins.formation.price}
                                                />
                                                <div className="card-action">
                                                    <button className="btn-primary" onClick={() => terminer(ins.id)}>Marquer terminée</button>
                                                    <button className="btn-danger" onClick={() => seDesinscrire(ins.id)}>Se désinscrire</button>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>

                            {/* --- SECTION FORMATIONS TERMINÉES --- */}
                            <div className='FormationTerminee'>
                                <h2>Mes formations terminées:</h2>
                                <div className='Cards_FormationTerminee'>
                                    {loadingInscriptions ? <p>Chargement...</p> : terminees.length === 0 ? <p>Aucune formation terminée pour le moment.</p> : (
                                        terminees.map((ins) => (
                                            <div className="cardA" key={ins.id}>
                                                <FormationCard
                                                    photo={ins.formation.photo || photo_2}
                                                    altText={ins.formation.title}
                                                    title={ins.formation.title}
                                                    name={ins.formation.pseudo_formateur || "Formateur"}
                                                    categorie={ins.formation.categorie}
                                                    niveau={levelLabel(ins.formation.level)}
                                                    temps={durationLabel(ins.formation.duration)}
                                                    ville={ins.formation.ville}
                                                    prix={ins.formation.price}
                                                />
                                                <div className="card-action">
                                                    <span className="badge badge-done">Terminée ✓</span>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                )}
                <Footer />
            </div>
        </main>
    );
}

export default Apprenant;
