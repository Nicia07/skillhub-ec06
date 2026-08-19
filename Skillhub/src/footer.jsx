import { Link } from 'react-router-dom';
import './footer.css';

const footer = () => {

    return (
        <footer>
            <div className="footer-container">

                <div className="footer-col1">
                    <h3>SkillHub</h3>
                    <p>La plateforme pour apprendre et partager vos compétences.</p>
                </div>

                <div className="footer-col">
                    <h3>Liens Utiles</h3>
                    <ul>
                        <li><Link to="/">Accueil</Link></li>
                        <li><Link to="/Formation">Formations</Link></li>
                        <li><a href="#">À Propos</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>

                <div className="footer-col">
                    <h3>Contact</h3>
                    <ul>
                        <li>contact@skillhub.com</li>
                        <li>+33 1 23 45 67 89</li>
                    </ul>
                    <div className="social-icons">
                        <span>[FB]</span> <span>[Insta]</span> <span>[In]</span>
                    </div>
                </div>

            </div>
            <br />
            <br />
            <hr />
            <div className="footer-bottom">
                <p>&copy; @2025 SkillHub, Tout droit réservés.</p>
            </div>
        </footer>
    )
}

export default footer;