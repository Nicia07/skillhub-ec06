const FormationCard = ({ photo, altText, title, name, categorie, niveau, temps, ville, prix }) => {
    return (
        <div className="card">
            <img src={photo} alt={altText || "photo profil"} loading="lazy" />
            <div className='container'>
                <h4>{title}</h4>
                <div className='nom'>{name}</div>
                {prix != null && prix !== '' && (
                    <div className='prix-tag'>
                        {prix} €
                    </div>
                )}
            </div>
            <div className='info'>
                <ul>
                    <li>
                        <span><b>Catégorie:</b></span>
                        <span>{categorie}</span>
                        <br />
                        <span><b>Niveau:</b></span>
                        <span>{niveau}</span>
                    </li>
                    <li>
                        <span><b>Temps:</b></span>
                        <span>{temps}</span>
                        <br />
                        <span><b>Ville:</b></span>
                        <span>{ville}</span>
                    </li>
                </ul>
            </div>
        </div>
    );
};

export default FormationCard;