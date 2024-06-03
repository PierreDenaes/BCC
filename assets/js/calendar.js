import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';  // Importation de la locale française
import '../styles/profileform.scss';
// Fonction pour initialiser FullCalendar
function initializeCalendar() {
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
    }
}

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

            // Initialiser les champs
            const periodField = document.getElementById('booking_period').closest('.mb-3');
            const productField = document.getElementById('booking_product');

            // Fonction pour basculer la visibilité du champ de période
            function togglePeriodField() {
                if (parseInt(productField.value) === 3) { // Assuming "3" is the value for "1/2 journée"
                    periodField.style.display = '';
                } else {
                    periodField.style.display = 'none';
                }
            }

            // Initial check
            togglePeriodField();

            // Add event listener
            productField.addEventListener('change', togglePeriodField);

            // Gérer l'affichage des participants
            const isGroupCheckbox = document.getElementById('booking_isGroup');
            const participantsContainer = document.getElementById('participantsContainer');
            const participantList = document.getElementById('participant-list');
            const addParticipantButton = document.getElementById('add-participant');
            let index = participantList.children.length;

            function toggleParticipantsContainer() {
                if (isGroupCheckbox.checked) {
                    participantsContainer.style.display = '';
                } else {
                    participantsContainer.style.display = 'none';
                }
            }

            // Initial check
            toggleParticipantsContainer();

            // Add event listener
            isGroupCheckbox.addEventListener('change', toggleParticipantsContainer);

            // Add participant
            addParticipantButton.addEventListener('click', () => {
                let newLi = document.createElement('li');
                let newWidget = participantList.dataset.prototype.replace(/__name__/g, index++);
                newLi.innerHTML = newWidget + '<button type="button" class="remove-participant btn btn-danger">Supprimer</button>';
                participantList.appendChild(newLi);
            });

            // Remove participant
            participantList.addEventListener('click', (e) => {
                if (e.target && e.target.classList.contains('remove-participant')) {
                    e.target.parentElement.remove();
                }
            });
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

// Initialiser FullCalendar lors du chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
});
