import { Calendar } from '@fullcalendar/core';
import listPlugin from '@fullcalendar/list';
import frLocale from '@fullcalendar/core/locales/fr';  // Importation de la locale française
import 'swiper/swiper-bundle.css';
import '../styles/page/home.scss';
  
let calendar;
function initializeCalendar() {
    const calendarEl = document.getElementById('calendarIndex');
    if (calendarEl) {
        calendar = new Calendar(calendarEl, {
            plugins: [listPlugin], // Supprimer interactionPlugin si tu veux désactiver les clics
            initialView: 'listMonth',
            locale: frLocale,
            events: '/bookings', // Chargement des dates réservées
            eventClick: function() { // Redirection globale
                window.location.href = '/booking';
            }
        });
        calendar.render();
    }
}


document.addEventListener('DOMContentLoaded', () => {
    const swiper = new Swiper(".mySwiper", {
        effect: "coverflow",
        grabCursor: true,
        centeredSlides: true,
        slidesPerView: "auto",

        coverflowEffect: {
            rotate: 50,
            stretch: 0,
            depth: 100,
            modifier: 1,
            slideShadows: false
        },
        pagination: {
            el: ".swiper-pagination"
        }
    });
    initializeCalendar();

});
document.querySelector('.arrow').addEventListener('click', function(event) {
    event.preventDefault();
    document.querySelector('#accueilInstructor').scrollIntoView({
        behavior: 'smooth'
    });
});
