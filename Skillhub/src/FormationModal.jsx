import React, { useState, useEffect } from 'react';
import api from './api';
import { LEVEL_OPTIONS } from './formationDisplay';
import './AuthModal.css';

const FormationModal = ({ close, mode, initialData, refreshFormations }) => {

    const [formData, setFormData] = useState({
        title: '',
        description: '',
        categorie: '',
        level: '',
        ville: '',
        duration: '',
        price: ''
    });
    const [error, setError] = useState("");

    useEffect(() => {
        if (mode === 'edit' && initialData) {
            setFormData({
                title: initialData.title || '',
                description: initialData.description || '',
                categorie: initialData.categorie || '',
                level: initialData.level || '',
                ville: initialData.ville || '',
                duration: initialData.duration ?? '',
                price: initialData.price ?? ''
            });
        }
    }, [mode, initialData]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError("");

        try {
            const payload = {
                ...formData,
                duration: formData.duration === '' ? '' : Number(formData.duration),
                price: formData.price === '' ? '' : Number(formData.price),
            };
            if (mode === 'create') {
                await api.post('/formations', payload);
                alert("Formation créée avec succès !");
            } else {
                await api.put(`/formations/${initialData.id}`, payload);
                alert("Formation modifiée avec succès !");
            }
            refreshFormations();
            close();
        } catch (err) {
            console.error(err);

            if (err.response && err.response.status === 422) {

                const erreursLaravel = err.response.data.errors;

                const premierMessage = Object.values(erreursLaravel)[0][0];
                setError(`Erreur : ${premierMessage}`);

            }
            else if (err.response && err.response.status === 401) {
                setError("Votre session a expiré. Veuillez vous reconnecter.");
            }
            else if (err.response && err.response.status === 403) {
                setError("Action non autorisée.");
            }
            else {
                setError("Une erreur inattendue est survenue avec le serveur.");
            }
        }
    };

    return (
        <div className="modal-overlay" onClick={close}>
            <div className="modal-card" onClick={(e) => e.stopPropagation()}>
                <button className="close-btn" onClick={close}>&times;</button>
                <div className="modal-header">
                    <h2>{mode === 'create' ? "Créer une formation" : "Modifier la formation"}</h2>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="input-group">
                        <label>Titre de la formation</label>
                        <input type="text" required value={formData.title} onChange={(e) => setFormData({...formData, title: e.target.value})} />
                    </div>

                    <div className="input-group">
                        <label>Description</label>
                        <textarea required value={formData.description} onChange={(e) => setFormData({...formData, description: e.target.value})} style={{width: '100%', padding: '10px', borderRadius: '5px', border: '1px solid #ccc'}} rows="3" />
                    </div>

                    <div style={{ display: 'flex', gap: '10px' }}>
                        <div className="input-group" style={{ flex: 1 }}>
                            <label>Catégorie</label>
                            <select value={formData.categorie} onChange={(e) => setFormData({...formData, categorie: e.target.value})}>
                                <option value="" disabled>Choisir...</option>
                                <option value="Front-end">Front-end</option>
                                <option value="Back-end">Back-end</option>
                                <option value="Full Stack">Full Stack</option>
                            </select>
                        </div>
                        <div className="input-group" style={{ flex: 1 }}>
                            <label>Niveau</label>
                            <select required value={formData.level} onChange={(e) => setFormData({...formData, level: e.target.value})}>
                                <option value="" disabled>Choisir...</option>
                                {LEVEL_OPTIONS.map(({ value, label }) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div style={{ display: 'flex', gap: '10px' }}>
                        <div className="input-group" style={{ flex: 1 }}>
                            <label>Ville</label>
                            <input type="text" value={formData.ville} placeholder="Ex: Lyon, Quimper..." onChange={(e) => setFormData({...formData, ville: e.target.value})} />
                        </div>
                        <div className="input-group" style={{ flex: 1 }}>
                            <label>Durée (heures)</label>
                            <input required type="number" min="1" step="1" value={formData.duration} placeholder="Ex: 25" onChange={(e) => setFormData({...formData, duration: e.target.value})} />
                        </div>
                    </div>

                    <div className="input-group">
                        <label>Prix (€)</label>
                        <input required type="number" min="0" step="0.01" value={formData.price} placeholder="Ex: 30" onChange={(e) => setFormData({...formData, price: e.target.value})} />
                    </div>

                    {error && <p className="error-text" style={{color: 'red'}}>{error}</p>}
                    <button type="submit" className="submit-btn">Enregistrer</button>
                </form>
            </div>
        </div>
    );
};

export default FormationModal;
