import * as bootstrap from 'bootstrap';
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

var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})

window.bootstrap = bootstrap;
