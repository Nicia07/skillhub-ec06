// La base stocke `level` en anglais (contrainte de l'énoncé EC04 : enum
// beginner/intermediate/advanced). Ces helpers centralisent la traduction
// pour l'affichage et les filtres, sans dupliquer la table de correspondance
// dans chaque composant.
export const LEVEL_OPTIONS = [
    { value: 'beginner', label: 'Débutant' },
    { value: 'intermediate', label: 'Intermédiaire' },
    { value: 'advanced', label: 'Avancé' },
];

const LEVEL_LABELS = Object.fromEntries(LEVEL_OPTIONS.map(o => [o.value, o.label]));

export const levelLabel = (level) => LEVEL_LABELS[level] || level || '';

export const durationLabel = (duration) => (duration !== null && duration !== undefined && duration !== '' ? `${duration}h` : '');
