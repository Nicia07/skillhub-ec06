import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from './api';
import { useAuth } from './AuthContext';
import './AuthModal.css';

const AuthModal = ({ mode, setMode, close }) => {
    const navigate = useNavigate();
    const { login } = useAuth();
    const [formData, setFormData] = useState({
        pseudo: '', email: '', password: '', role: 'apprenant'
    });
    const [error, setError] = useState("");

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError("");
        const url = mode === "login" ? "/login" : "/register";

        try {
            const res = await api.post(url, formData);

            login(res.data.token, res.data.user);
            close();

            // Redirection selon le rôle
            if (res.data.user.role === "formateur") {
                navigate("/");
            } else {
                navigate("/Apprenant");
            }

        } catch (err) {
            if (err.response && err.response.data) {
                const messages = Object.values(err.response.data).flat();
                setError(messages[0]);
            } else {
                setError("Erreur de connexion au serveur.");
            }
        }
    };

    return (
        <div className="modal-overlay" onClick={close}>
            <div className="modal-card" onClick={(e) => e.stopPropagation()}>
                <button className="close-btn" onClick={close}>&times;</button>
                <div className="modal-header">
                    <h2>{mode === "login" ? "Connexion" : "Inscription"}</h2>
                </div>
                <form onSubmit={handleSubmit}>
                    {mode === "register" && (
                        <div className="input-group">
                            <label>Pseudo</label>
                            <input type="text" required onChange={(e) => setFormData({...formData, pseudo: e.target.value})} />
                        </div>
                    )}
                    <div className="input-group">
                        <label>Email</label>
                        <input type="email" required onChange={(e) => setFormData({...formData, email: e.target.value})} />
                    </div>
                    <div className="input-group">
                        <label>Mot de passe</label>
                        <input type="password" required onChange={(e) => setFormData({...formData, password: e.target.value})} />
                    </div>
                    {mode === "register" && (
                        <div className="input-group">
                            <label>Rôle</label>
                            <select onChange={(e) => setFormData({...formData, role: e.target.value})}>
                                <option value="apprenant">Apprenant</option>
                                <option value="formateur">Formateur</option>
                            </select>
                        </div>
                    )}
                    {error && <p className="error-text">{error}</p>}
                    
                    {/* BOUTON ADAPTÉ SELON LE MODE */}
                    <button type="submit" className="submit-btn">
                        {mode === "login" ? "Se connecter" : "S'inscrire"}
                    </button>
                </form>
            </div>
        </div>
    );
};

export default AuthModal;