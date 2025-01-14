import 'bootstrap';
import 'lineicons/dist/lineicons.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
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
window.addEventListener('scroll', function() {
    const footer = document.getElementById('footer');
    
    // Si l'utilisateur a scrollé de plus de 50px
    if (window.scrollY > 50) {
        footer.classList.add('footer-normal');
    } else {
        footer.classList.remove('footer-normal');
    }
});

