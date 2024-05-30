import './bootstrap.js';
import Swiper from 'swiper';
import 'swiper/swiper-bundle.css';
require('bootstrap');
require('lineicons/web-font/lineicons.css');
// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

const hamburger = document.querySelector('#toggle-btn')

hamburger.addEventListener('click', () => {
    document.querySelector('#sidebar').classList.toggle('expand')
})
// assets/js/app.js



