import React, { useState } from 'react';
import { useNavigate } from "react-router-dom";
import AuthModal from './AuthModal';
import { useAuth } from './AuthContext';
import './navbar.css';

const Navbar = () => {
    const navigate = useNavigate();
    const { user, isLoggedIn, logout } = useAuth();

    // États pour les menus
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("login");
    const [afficherMenuBurger, setAfficherMenuBurger] = useState(false);

    // Fonctions de navigation
    const navFormateur = () => { navigate('/'); menuClose(); };
    const navApprenant = () => { navigate('/Apprenant'); menuClose(); };
    const navFormation = () => { navigate('/Formation'); menuClose(); };

    // GESTION DE LA DÉCONNEXION
    const handleLogout = () => {
        logout();
        navigate('/');
        menuClose();
    };

    // Gestion de la modale
    const openModal = (mode) => {
        setModalMode(mode);
        setIsModalOpen(true);
    };

    // Gestion du menu Burger
    const menuOpen = () => setAfficherMenuBurger(true);
    const menuClose = () => setAfficherMenuBurger(false);

    return (
        <nav className="main-nav">
            {/* Menu mobile (Burger) */}
            <button className="burgerBtn" onClick={menuOpen}>☰ Menu</button>

            <div className={`menuBurger ${afficherMenuBurger ? 'open' : ''}`}>
                <span className="x" onClick={menuClose}>&times;</span>
                <div className="details-menu">
                    <button onClick={navFormation}>Formation</button>
                    <button onClick={navFormateur}>Formateur</button>
                    <button onClick={navApprenant}>Apprenant</button>
                </div>
            </div>

            {/* Logo */}
            <div className="logo_text">
                <button onClick={navFormateur} className="logo-btn">
                    <b>SkillHub</b>
                </button>
            </div>

            {/* Menu Desktop */}
            <div className="menu">
                <button onClick={navFormation}>Formation</button>
                <button onClick={navFormateur}>Formateur</button>
                <button onClick={navApprenant}>Apprenant</button>
            </div>

            {/* Boutons d'authentification dynamiques */}
            <div className="nav_button">
                {isLoggedIn ? (
                    <>
                        <span className="user-info" style={{alignSelf: 'center', fontWeight: 'bold', color: '#333'}}>
                            {user?.pseudo} <span className="role-badge">({user?.role})</span>
                        </span>
                        <button
                            type="button"
                            className="Sign_in"
                            onClick={handleLogout}
                        >
                            Déconnexion
                        </button>
                    </>
                ) : (
                    <>
                        <button
                            type="button"
                            className="Sign_in"
                            onClick={() => openModal("login")}
                        >
                            Connexion
                        </button>
                        <button
                            type="button"
                            className="Log_in"
                            onClick={() => openModal("register")}
                        >
                            S'inscrire
                        </button>
                    </>
                )}
            </div>

            {/* Appel de la modale */}
            {isModalOpen && (
                <AuthModal
                    mode={modalMode}
                    setMode={setModalMode}
                    close={() => setIsModalOpen(false)}
                />
            )}
        </nav>
    );
};

export default Navbar;
