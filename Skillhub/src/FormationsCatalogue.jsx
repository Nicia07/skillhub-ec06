import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import FormationCard from './FormationCard';
import api from './api';
import { useAuth } from './AuthContext';
import { levelLabel, durationLabel } from './formationDisplay';
import photo_2 from './assets/profil.webp';

// Catalogue de toutes les formations : recherche, filtre, et action "Suivre"
// pour un apprenant connecté. Autonome (gère son propre fetch), réutilisable
// sur n'importe quelle page (page Formation publique, dashboard Apprenant...).
const FormationsCatalogue = ({ onChange }) => {
    const { user, isLoggedIn } = useAuth();
    const isApprenant = isLoggedIn && user?.role === 'apprenant';

    const [formations, setFormations] = useState([]);
    const [inscriptions, setInscriptions] = useState([]);
    const [loadingCatalogue, setLoadingCatalogue] = useState(true);
    const [actionError, setActionError] = useState("");

    const [searchTerm, setSearchTerm] = useState("");
    const [activeFilters, setActiveFilters] = useState({ categories: [], niveaux: [], villes: [] });

    const fetchCatalogue = async () => {
        try {
            setLoadingCatalogue(true);
            const res = await api.get('/formations');
            setFormations(res.data);
        } catch (error) {
            console.error("Erreur catalogue:", error.response ? error.response.status : error.message);
        } finally {
            setLoadingCatalogue(false);
        }
    };

    const fetchInscriptions = async () => {
        try {
            const res = await api.get('/mes-inscriptions');
            setInscriptions(res.data);
        } catch (error) {
            console.error("Erreur inscriptions:", error.response ? error.response.status : error.message);
        }
    };

    useEffect(() => {
        fetchCatalogue();
    }, []);

    useEffect(() => {
        if (isApprenant) {
            fetchInscriptions();
        } else {
            setInscriptions([]);
        }
    }, [isApprenant]);

    const suivre = async (idFormation) => {
        setActionError("");
        try {
            await api.post('/inscriptions', { id_formation: idFormation });
            await fetchInscriptions();
            onChange?.();
        } catch (error) {
            setActionError(error.response?.data?.message || "Erreur lors du suivi de la formation.");
        }
    };

    const handleFilterChange = (category, value) => {
        setActiveFilters(prev => {
            const current = prev[category];
            if (current.includes(value)) {
                return { ...prev, [category]: current.filter(item => item !== value) };
            } else {
                return { ...prev, [category]: [...current, value] };
            }
        });
    };

    let filteredFormations = formations;
    if (searchTerm.trim() !== "") {
        filteredFormations = filteredFormations.filter(f =>
            f.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
            (f.description && f.description.toLowerCase().includes(searchTerm.toLowerCase()))
        );
    }
    if (activeFilters.categories.length > 0) {
        filteredFormations = filteredFormations.filter(f => activeFilters.categories.includes(f.categorie?.toLowerCase()));
    }
    if (activeFilters.niveaux.length > 0) {
        filteredFormations = filteredFormations.filter(f => activeFilters.niveaux.includes(f.level));
    }
    if (activeFilters.villes.length > 0) {
        filteredFormations = filteredFormations.filter(f => activeFilters.villes.includes(f.ville?.toLowerCase()));
    }

    const inscriptionsParFormation = {};
    inscriptions.forEach(ins => { inscriptionsParFormation[ins.id_formation] = ins; });

    return (
        <>
            <div className="search">
                <div className="search-details">
                    <form className="inputSearch" onSubmit={(e) => e.preventDefault()}>
                        <div className="boxSearch">
                            <span className="search_symbol">⌕</span>
                            <input
                                type="text"
                                className="SearchInput"
                                aria-label="Rechercher"
                                placeholder="Rechercher une formation..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                            />
                        </div>
                    </form>
                </div>
            </div>

            {actionError && <p className="error-text center">{actionError}</p>}

            <div className='pageApprenant'>
                <div className="filter">
                    <h3>Filtre:</h3>
                    <ul>
                        <li>
                            <h4>Catégories:</h4>
                            <span><input type="checkbox" onChange={() => handleFilterChange('categories', 'back-end')} /><label>Back-end</label></span>
                            <span><input type="checkbox" onChange={() => handleFilterChange('categories', 'front-end')} /><label>Front-end</label></span>
                            <span><input type="checkbox" onChange={() => handleFilterChange('categories', 'full stack')} /><label>Full Stack</label></span>
                        </li>
                        <li>
                            <h4>Niveau:</h4>
                            <span><input type="checkbox" onChange={() => handleFilterChange('niveaux', 'beginner')} /><label>Débutant</label></span>
                            <span><input type="checkbox" onChange={() => handleFilterChange('niveaux', 'intermediate')} /><label>Intermédiaire</label></span>
                            <span><input type="checkbox" onChange={() => handleFilterChange('niveaux', 'advanced')} /><label>Avancé</label></span>
                        </li>
                        <li>
                            <h4>Ville:</h4>
                            <span><input type="checkbox" onChange={() => handleFilterChange('villes', 'quimper')} /><label>Quimper</label></span>
                            <span><input type="checkbox" onChange={() => handleFilterChange('villes', 'lyon')} /><label>Lyon</label></span>
                            <span><input type="checkbox" onChange={() => handleFilterChange('villes', 'strasbourg')} /><label>Strasbourg</label></span>
                        </li>
                    </ul>
                </div>

                <div className='Apprenant-cards'>
                    <div className='FormationCatalogue'>
                        <h2>Formations disponibles:</h2>
                        {!isLoggedIn && (
                            <p className="info-text">Connectez-vous en tant qu'apprenant pour suivre une formation et enregistrer votre progression.</p>
                        )}
                        <div className="Cards_FormationSuivie">
                            {loadingCatalogue ? <p>Chargement...</p> : filteredFormations.length === 0 ? <p>Aucune formation ne correspond à votre recherche.</p> : (
                                filteredFormations.map((formation) => {
                                    const inscription = inscriptionsParFormation[formation.id];
                                    return (
                                        <div className="cardA" key={formation.id}>
                                            <FormationCard
                                                photo={formation.photo || photo_2}
                                                altText={formation.title}
                                                title={formation.title}
                                                name={formation.pseudo_formateur || "Formateur"}
                                                categorie={formation.categorie}
                                                niveau={levelLabel(formation.level)}
                                                temps={durationLabel(formation.duration)}
                                                ville={formation.ville}
                                                prix={formation.price}
                                            />
                                            <div className="card-action">
                                                <Link to={`/Formation/${formation.id}`} className="btn-secondary" style={{ textDecoration: 'none', display: 'inline-block' }}>Voir détail</Link>
                                                {!isApprenant ? (
                                                    <button className="btn-secondary" disabled>Connectez-vous pour suivre</button>
                                                ) : inscription?.status === 'terminée' ? (
                                                    <span className="badge badge-done">Terminée ✓</span>
                                                ) : inscription ? (
                                                    <span className="badge badge-progress">Déjà suivie</span>
                                                ) : (
                                                    <button className="btn-primary" onClick={() => suivre(formation.id)}>Suivre</button>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default FormationsCatalogue;
