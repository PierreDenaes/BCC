import 'bootstrap';
import 'lineicons/web-font/lineicons.css';
import './styles/app.scss';



const hamburger = document.querySelector('#toggle-btn');

hamburger.addEventListener('click', () => {
    document.querySelector('#sidebar').classList.toggle('expand');
});

