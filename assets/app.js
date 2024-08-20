import 'bootstrap';
import 'lineicons/web-font/lineicons.css';
import './styles/app.scss';



const hamburger = document.querySelector('#toggle-btn');
const sidebar = document.querySelector('#sidebar');

hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('expand');
});

// Ferme le menu si on clique en dehors de celui-ci
document.addEventListener('click', (event) => {
    const isClickInside = sidebar.contains(event.target) || hamburger.contains(event.target);

    if (!isClickInside && sidebar.classList.contains('expand')) {
        sidebar.classList.remove('expand');
    }
});

