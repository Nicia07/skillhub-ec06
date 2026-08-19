import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from './api';
import { useAuth } from './AuthContext';
import Navbar from './Navbar';
import Footer from './footer';
import FormationCard from './FormationCard';
import FormationModal from './FormationModal';
import { levelLabel, durationLabel } from './formationDisplay';
import photo_2 from './assets/profil.webp';
import './Formateur.css';
import './shared.css';

const Formateur = () => {
    const { user, isLoggedIn, loading: authLoading } = useAuth();
    const navigate = useNavigate();

    const [formations, setFormations] = useState([]);
    const [filteredFormations, setFilteredFormations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [errorMsg, setErrorMsg] = useState("");

    const [searchTerm, setSearchTerm] = useState("");
    const [activeFilters, setActiveFilters] = useState({ categories: [], niveaux: [], villes: [] });
    const [priceMin, setPriceMin] = useState("");
    const [priceMax, setPriceMax] = useState("");

    // États pour gérer la modale
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [currentData, setCurrentData] = useState(null);

    const isFormateur = isLoggedIn && user?.role === 'formateur';

    useEffect(() => {
        if (!authLoading && isLoggedIn && user?.role !== 'formateur') {
            navigate('/Apprenant', { replace: true });
        }
    }, [authLoading, isLoggedIn, user, navigate]);

    const fetchFormations = async () => {
        try {
            setLoading(true);
            const response = await api.get("/my-formations");
            setFormations(response.data);
            setFilteredFormations(response.data);
        } catch (error) {
            console.error("Erreur API:", error.response ? error.response.status : error.message);
            setErrorMsg("Impossible de charger vos formations.");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (isFormateur) {
            fetchFormations();
        } else {
            setLoading(false);
        }
    }, [isFormateur]);

    useEffect(() => {
        let result = formations;
        if (searchTerm.trim() !== "") {
            result = result.filter(f =>
                f.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                (f.description && f.description.toLowerCase().includes(searchTerm.toLowerCase()))
            );
        }
        if (activeFilters.categories.length > 0) {
            result = result.filter(f => activeFilters.categories.includes(f.categorie?.toLowerCase()));
        }
        if (activeFilters.niveaux.length > 0) {
            result = result.filter(f => activeFilters.niveaux.includes(f.level));
        }
        if (activeFilters.villes.length > 0) {
            result = result.filter(f => activeFilters.villes.includes(f.ville?.toLowerCase()));
        }
        if (priceMin !== "") {
            result = result.filter(f => Number(f.price) >= Number(priceMin));
        }
        if (priceMax !== "") {
            result = result.filter(f => Number(f.price) <= Number(priceMax));
        }
        setFilteredFormations(result);
    }, [searchTerm, activeFilters, priceMin, priceMax, formations]);

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

    // --- FONCTIONS CRUD ---

    const ouvrirModaleAjout = () => {
        setModalMode('create');
        setCurrentData(null);
        setIsModalOpen(true);
    };

    const modifier = (id) => {
        const formationAEditer = formations.find(f => f.id === id);
        setCurrentData(formationAEditer);
        setModalMode('edit');
        setIsModalOpen(true);
    };

    const supprimer = async (id) => {
        if (window.confirm("Voulez-vous vraiment supprimer cette formation ?")) {
            try {
                await api.delete(`/formations/${id}`);
                setFormations(formations.filter(f => f.id !== id));
            } catch (error) {
                console.error(error);
                alert("Erreur lors de la suppression.");
            }
        }
    };

    if (authLoading) {
        return null;
    }

    if (!isFormateur) {
        return (
            <div className="DashboardFormateur">
                <Navbar />
                <div className="auth-gate">
                    <h1>Espace formateur</h1>
                    <p>Connectez-vous avec un compte formateur pour créer et gérer vos formations.</p>
                </div>
                <Footer />
            </div>
        );
    }

    return (
        <div className="DashboardFormateur">
            <Navbar />

            <div className="search_filter">
                <div className="search">
                    <form className="inputSearch" onSubmit={(e) => e.preventDefault()}>
                        <div className="boxSearch">
                            <span className="search_symbol">⌕</span>
                            <input type="text" className="SearchInput" placeholder="Rechercher..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} />
                        </div>
                    </form>
                </div>
            </div>

            <div className="dashboard-header">
                <h1>Tableau de bord Formateur</h1>
                <button onClick={ouvrirModaleAjout} className="btn-primary">
                    + Ajouter une formation
                </button>
            </div>

            <div className='pageFormateur'>
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
                        </li>
                        <li>
                            <h4>Prix (€):</h4>
                            <span>
                                <input type="number" min="0" placeholder="Min" style={{ width: '70px' }} value={priceMin} onChange={(e) => setPriceMin(e.target.value)} />
                                <label>à</label>
                                <input type="number" min="0" placeholder="Max" style={{ width: '70px' }} value={priceMax} onChange={(e) => setPriceMax(e.target.value)} />
                            </span>
                        </li>
                    </ul>
                </div>

                <div className="bloc-droite">
                    <h2>Formations publiées:</h2>
                    <div className="CardsFormateur">
                        {loading ? <p>Chargement...</p> : errorMsg ? <p className="error-text">{errorMsg}</p> : filteredFormations.length > 0 ? (
                            filteredFormations.map((formation) => (
                                <div className="cardF" key={formation.id}>
                                    <div className='boutonCard'>
                                        <span className="material-symbols-outlined edit-icon" onClick={() => modifier(formation.id)}>✎</span>
                                        <span className="material-symbols-outlined delete-icon" onClick={() => supprimer(formation.id)}>🗑</span>
                                    </div>
                                    <FormationCard photo={formation.photo || photo_2} altText={formation.title} title={formation.title} name={formation.pseudo_formateur || "Formateur"} categorie={formation.categorie} niveau={levelLabel(formation.level)} temps={durationLabel(formation.duration)} ville={formation.ville} prix={formation.price} />
                                </div>
                            ))
                        ) : <p>Aucune formation. Cliquez sur "+ Ajouter une formation" pour commencer.</p>}
                    </div>
                </div>
            </div>

            {/* APPEL DE LA MODALE */}
            {isModalOpen && (
                <FormationModal
                    mode={modalMode}
                    initialData={currentData}
                    close={() => setIsModalOpen(false)}
                    refreshFormations={fetchFormations}
                />
            )}

            <Footer />
        </div >
    );
}

export default Formateur;
