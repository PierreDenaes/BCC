import './bootstrap.js';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import Swiper from 'swiper';
import 'swiper/swiper-bundle.css';
require('bootstrap');
require('lineicons/web-font/lineicons.css');
import './styles/app.scss';

const hamburger = document.querySelector('#toggle-btn');

hamburger.addEventListener('click', () => {
    document.querySelector('#sidebar').classList.toggle('expand');
});

// Configuration de FullCalendar
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        var calendar = new Calendar(calendarEl, {
            plugins: [ dayGridPlugin, interactionPlugin ],
            initialView: 'dayGridMonth',
            dateClick: function(info) {
                var forfait = calendarEl.dataset.forfait; // Récupérer le forfait à partir de l'attribut data
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
