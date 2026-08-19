import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from './api';
import { useAuth } from './AuthContext';
import Navbar from './Navbar';
import Footer from './footer';
import { levelLabel, durationLabel } from './formationDisplay';
import ReportProblemModal from './ReportProblemModal';
import photo_2 from './assets/profil.webp';
import './shared.css';

// Page détail d'une formation : accessible sans connexion (route publique
// GET /api/formations/{id}). Un apprenant connecté peut y suivre la formation.
const FormationDetail = () => {
    const { id } = useParams();
    const { user, isLoggedIn } = useAuth();
    const isApprenant = isLoggedIn && user?.role === 'apprenant';

    const [formation, setFormation] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notFound, setNotFound] = useState(false);
    const [inscription, setInscription] = useState(null);
    const [actionError, setActionError] = useState("");
    const [showReportModal, setShowReportModal] = useState(false);

    useEffect(() => {
        setLoading(true);
        setNotFound(false);
        api.get(`/formations/${id}`)
            .then((res) => setFormation(res.data))
            .catch((error) => {
                if (error.response?.status === 404) setNotFound(true);
            })
            .finally(() => setLoading(false));
    }, [id]);

    useEffect(() => {
        if (!isApprenant) {
            setInscription(null);
            return;
        }
        api.get('/mes-inscriptions')
            .then((res) => setInscription(res.data.find((ins) => ins.id_formation === Number(id))))
            .catch(() => {});
    }, [isApprenant, id]);

    const suivre = async () => {
        setActionError("");
        try {
            await api.post('/inscriptions', { id_formation: Number(id) });
            const res = await api.get('/mes-inscriptions');
            setInscription(res.data.find((ins) => ins.id_formation === Number(id)));
        } catch (error) {
            setActionError(error.response?.data?.message || "Erreur lors du suivi de la formation.");
        }
    };

    return (
        <main>
            <div className="Dashboard_Apprenant">
                <Navbar />
                <div style={{ maxWidth: 720, margin: '0 auto', padding: '20px' }}>
                    <p><Link to="/Formation">&larr; Retour aux formations</Link></p>

                    {loading ? (
                        <p>Chargement...</p>
                    ) : notFound ? (
                        <p className="error-text">Formation introuvable.</p>
                    ) : formation && (
                        <>
                            <img
                                src={formation.photo || photo_2}
                                alt={formation.title}
                                style={{ width: '100%', maxHeight: 320, objectFit: 'cover', borderRadius: 8 }}
                            />
                            <h1>{formation.title}</h1>
                            <p><b>Formateur :</b> {formation.pseudo_formateur || 'Formateur'}</p>
                            {formation.price != null && formation.price !== '' && (
                                <p><b>Prix :</b> {formation.price} €</p>
                            )}
                            <p><b>Catégorie :</b> {formation.categorie || '—'}</p>
                            <p><b>Niveau :</b> {levelLabel(formation.level)}</p>
                            <p><b>Durée :</b> {durationLabel(formation.duration)}</p>
                            <p><b>Ville :</b> {formation.ville || '—'}</p>
                            <p><b>Description :</b></p>
                            <p>{formation.description}</p>

                            {actionError && <p className="error-text">{actionError}</p>}

                            <div className="card-action" style={{ width: 'auto', justifyContent: 'flex-start' }}>
                                {!isApprenant ? (
                                    <button className="btn-secondary" disabled>Connectez-vous en tant qu'apprenant pour suivre</button>
                                ) : inscription?.status === 'terminée' ? (
                                    <span className="badge badge-done">Terminée ✓</span>
                                ) : inscription ? (
                                    <span className="badge badge-progress">Déjà suivie</span>
                                ) : (
                                    <button className="btn-primary" onClick={suivre}>Suivre</button>
                                )}
                            </div>

                            <p>
                                <button type="button" onClick={() => setShowReportModal(true)}>Signaler un problème</button>
                            </p>
                        </>
                    )}
                </div>

                {showReportModal && (
                    <ReportProblemModal idFormation={id} close={() => setShowReportModal(false)} />
                )}

                <Footer />
            </div>
        </main>
    );
};

export default FormationDetail;
