import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';  // Importation de la locale française
import '../styles/bookform.scss';

let calendar; // Variable globale pour stocker l'instance du calendrier

// Fonction pour obtenir toutes les dates entre deux dates
function getDatesInRange(startDate, endDate) {
    const dates = [];
    let currentDate = new Date(startDate);
    const end = new Date(endDate);

    while (currentDate <= end) {
        dates.push(new Date(currentDate).toISOString().split('T')[0]);
        currentDate.setDate(currentDate.getDate() + 1);
    }

    return dates;
}

// Fonction pour initialiser FullCalendar
function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');
    let reservedDates = [];
    let reservedHalfDays = {}; // Object to store half-day reservations

    if (calendarEl) {
        calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, interactionPlugin],
            initialView: 'dayGridMonth',
            locale: frLocale,  // Utilisation de la locale française
            dateClick: function(info) {
                const selectedDate = info.dateStr.split('T')[0];
                const forfait = calendarEl.dataset.forfait;  // Récupérer le forfait à partir de l'attribut data

                if (!reservedDates.includes(selectedDate)) {
                    openBookingForm(info.dateStr, forfait);
                } else if (reservedHalfDays[selectedDate] && reservedHalfDays[selectedDate].length < 2) {
                    openBookingForm(info.dateStr, forfait);
                }
            },
            events: {
                url: '/bookings',
                extraParams: function() {
                    return {
                        timezone: 'local'
                    };
                },
                failure: function() {
                    alert('Il y a eu une erreur lors de la récupération des événements.');
                },
                success: function(data) {
                    reservedDates = [];
                    reservedHalfDays = {};

                    data.forEach(event => {
                        const eventDates = getDatesInRange(event.start, event.end);
                        reservedDates = reservedDates.concat(eventDates);

                        // Handle half-day reservations
                        if (event.period) {
                            const eventDate = event.start.split('T')[0];
                            if (!reservedHalfDays[eventDate]) {
                                reservedHalfDays[eventDate] = [];
                            }
                            reservedHalfDays[eventDate].push(event.period);
                        }
                    });

                    return data.map(event => {
                        const eventDuration = (new Date(event.end) - new Date(event.start)) / (1000 * 60 * 60); // Durée en heures
                        if (eventDuration >= 9) {
                            event.classNames = ['long-event']; // Appliquer une classe CSS spécifique
                        }
                        return event;
                    });
                }
            },
            eventClassNames: function(arg) {
                const eventDuration = (new Date(arg.event.end) - new Date(arg.event.start)) / (1000 * 60 * 60); // Durée en heures
                if (eventDuration >= 9) {
                    return ['long-event']; // Retourne la classe 'long-event' pour les événements de longue durée
                }
                return [];
            },
            eventContent: function(arg) {
                // Personnaliser l'affichage des événements pour inclure l'heure de début et le titre
                let timeText = '';
                if (arg.event.start) {
                    const start = new Date(arg.event.start);
                    timeText = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
                return { html: `<div class="fc-event-time">${timeText}</div><div class="fc-event-title">${arg.event.title}</div>` };
            },
            selectable: true,
            selectAllow: function(selectInfo) {
                const selectedDate = selectInfo.startStr.split('T')[0];

                if (reservedDates.includes(selectedDate)) {
                    return false;
                }

                const period = selectInfo.start.getHours() < 12 ? 'morning' : 'afternoon';

                if (reservedHalfDays[selectedDate] && reservedHalfDays[selectedDate].includes(period)) {
                    return false;
                }

                return true;
            },
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

            // Sélectionner la période par défaut si aucune période n'est précisée
            if (parseInt(productField.value) === 3) {
                const periodSelect = document.getElementById('booking_period');
                if (!periodSelect.value) {
                    periodSelect.value = 'morning'; // Valeur par défaut pour la période
                }
            }

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

            // Vérification avant la soumission du formulaire
            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const formData = new FormData(form);
                fetch('/book', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        const errorContainer = document.getElementById('errorContainer');
                        if (errorContainer) {
                            errorContainer.innerHTML = data.error;
                        } else {
                            const errorDiv = document.createElement('div');
                            errorDiv.id = 'errorContainer';
                            errorDiv.style.color = 'red';
                            errorDiv.innerHTML = data.error;
                            form.prepend(errorDiv);
                        }
                    } else {
                        document.getElementById('bookingModal').style.display = 'none';
                        alert('Réservation effectuée avec succès!');
                        // Reload the calendar events
                        calendar.refetchEvents();
                    }
                })
                .catch(error => console.warn('Error submitting the form:', error));
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
