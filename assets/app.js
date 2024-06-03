import './bootstrap.js';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';  // Importation de la locale française
import Swiper from 'swiper';
import 'swiper/swiper-bundle.css';
import 'bootstrap';
import 'lineicons/web-font/lineicons.css';
import './styles/app.scss';

const hamburger = document.querySelector('#toggle-btn');

hamburger.addEventListener('click', () => {
    document.querySelector('#sidebar').classList.toggle('expand');
});

// Configuration de FullCalendar
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        const calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, interactionPlugin],
            initialView: 'dayGridMonth',
            locale: frLocale,  // Utilisation de la locale française
            dateClick: function(info) {
                const forfait = calendarEl.dataset.forfait;  // Récupérer le forfait à partir de l'attribut data
                openBookingForm(info.dateStr, forfait);
            },
            events: '/bookings',
            eventColor: '#ff0000',
            selectable: true,
            selectOverlap: function(event) {
                return event.rendering === 'background';
            }
        });

        calendar.render();

        // Fonction pour ouvrir le formulaire de réservation
        function openBookingForm(date, forfait) {
            fetch(`/booking/form?date=${date}&forfait=${forfait}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('bookingFormContainer').innerHTML = html;
                    document.getElementById('bookingModal').style.display = 'block';

                    // Ajouter la date dans un champ caché du formulaire
                    const form = document.querySelector('#bookingForm');
                    const hiddenDateInput = document.createElement('input');
                    hiddenDateInput.type = 'hidden';
                    hiddenDateInput.name = 'bookAt';
                    hiddenDateInput.value = date + " 08:00:00"; // Ajouter l'heure fixe
                    form.appendChild(hiddenDateInput);
                    
                    // Ajout d'un débogage
                    console.log('Form data:', new FormData(form));
                })
                .catch(error => console.warn('Error fetching the form:', error));
        }

        // Fermer la modal
        document.querySelector('.close').onclick = function() {
            document.getElementById('bookingModal').style.display = 'none';
        };

        // Fermer la modal en cliquant en dehors de celle-ci
        window.onclick = function(event) {
            if (event.target === document.getElementById('bookingModal')) {
                document.getElementById('bookingModal').style.display = 'none';
            }
        };
    }
});
