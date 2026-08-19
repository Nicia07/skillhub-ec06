import React, { useState } from 'react';
import api from './api';

// Modal volontairement simple (pas de style élaboré) : select obligatoire
// pour le motif de signalement + boutons Envoyer / Annuler.
const MOTIFS = [
    { value: 'contenu_inapproprie', label: 'Contenu inapproprié' },
    { value: 'erreur_technique', label: 'Erreur technique' },
    { value: 'autre', label: 'Autre' },
];

const ReportProblemModal = ({ idFormation, close }) => {
    const [motif, setMotif] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setSubmitting(true);
        try {
            await api.post(`/formations/${idFormation}/signalements`, { motif });
            alert('Signalement envoyé, merci.');
            close();
        } catch (err) {
            setError(err.response?.data?.message || 'Erreur lors de l\'envoi du signalement.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div style={{ position: 'fixed', top: 0, left: 0, width: '100%', height: '100%', background: 'rgba(0,0,0,0.5)', display: 'flex', justifyContent: 'center', alignItems: 'center', zIndex: 1000 }}>
            <div style={{ background: 'white', padding: '20px', minWidth: '280px' }}>
                <h3>Signaler un problème</h3>
                <form onSubmit={handleSubmit}>
                    <div style={{ marginBottom: '15px' }}>
                        <label htmlFor="motif">Motif</label><br />
                        <select id="motif" required value={motif} onChange={(e) => setMotif(e.target.value)}>
                            <option value="" disabled>Choisir un motif...</option>
                            {MOTIFS.map(({ value, label }) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                    </div>
                    {error && <p className="error-text">{error}</p>}
                    <button type="submit" disabled={submitting}>{submitting ? 'Envoi...' : 'Envoyer'}</button>
                    <button type="button" onClick={close} disabled={submitting}>Annuler</button>
                </form>
            </div>
        </div>
    );
};

export default ReportProblemModal;
