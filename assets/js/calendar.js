import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';  // Importation de la locale française
import '../styles/page/bookform.scss';
import '../styles/page/modal.scss';

let calendar; // Variable globale pour stocker l'instance du calendrier

// Fonction pour obtenir toutes les dates entre deux dates
function getDatesInRange(startDate, endDate) {
    const dates = [];
    let currentDate = new Date(startDate); // Convertir la date en objet Date
    const end = new Date(endDate); // Convertir la date en objet Date

    while (currentDate <= end) { // Boucler jusqu'à la date de fin
        dates.push(new Date(currentDate).toISOString().split('T')[0]); // Ajouter la date au tableau
        currentDate.setDate(currentDate.getDate() + 1); // Incrémenter la date
    }

    return dates; // Retourner le tableau de dates
}

// Fonction pour initialiser FullCalendar
function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');
    let reservedDates = []; // Tableau pour stocker les dates réservées
    let reservedHalfDays = {}; // Objet pour stocker les demi-journées réservées

    if (calendarEl) { // Vérifier si l'élément #calendar existe
        calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, interactionPlugin], // Charger les plugins
            initialView: 'dayGridMonth',    // Vue initiale du calendrier
            locale: frLocale,  // Utilisation de la locale française
            dateClick: function(info) { // Gérer le clic sur une date
                const selectedDate = info.dateStr.split('T')[0]; // Extraire la date
                const forfait = calendarEl.dataset.forfait;  // Récupérer le forfait à partir de l'attribut data

                // Vérifier si la date est passée ou est aujourd'hui
                const today = new Date().toISOString().split('T')[0];
                if (selectedDate <= today) {
                    alert("Les réservations pour aujourd'hui ou les dates passées ne sont pas autorisées.");
                    return;
                }

                if (!reservedDates.includes(selectedDate)) { // Vérifier si la date est réservée
                    openBookingForm(info.dateStr, forfait); // Ouvrir le formulaire de réservation
                } else if (reservedHalfDays[selectedDate] && reservedHalfDays[selectedDate].length < 2) { // Vérifier si une demi-journée est disponible
                    openBookingForm(info.dateStr, forfait); // Ouvrir le formulaire de réservation
                }
            },
            events: { // Récupérer les événements à partir de l'API
                url: '/bookings',
                extraParams: function() {
                    return {
                        timezone: 'local' // Utiliser le fuseau horaire local
                    };
                },
                failure: function() {
                    alert('Il y a eu une erreur lors de la récupération des événements.'); // Afficher une alerte en cas d'erreur
                },
                success: function(data) {
                    reservedDates = []; // Réinitialiser les dates réservées
                    reservedHalfDays = {}; // Réinitialiser les demi-journées réservées

                    data.forEach(event => { // Boucler sur les événements
                        const eventDates = getDatesInRange(event.start, event.end); // Obtenir toutes les dates entre la date de début et la date de fin
                        reservedDates = reservedDates.concat(eventDates); // Ajouter les dates au tableau

                        // Stocker les demi-journées réservées
                        if (event.period) { // Vérifier si la période est précisée
                            const eventDate = event.start.split('T')[0]; // Extraire la date
                            if (!reservedHalfDays[eventDate]) { // Vérifier si la date existe dans l'objet
                                reservedHalfDays[eventDate] = []; // Initialiser un tableau vide
                            }
                            reservedHalfDays[eventDate].push(event.period); // Ajouter la période au tableau
                        }
                    });

                    return data.map(event => { // Retourner les événements
                        const eventDuration = (new Date(event.end) - new Date(event.start)) / (1000 * 60 * 60); // Durée en heures
                        if (eventDuration >= 9) { // Vérifier si la durée est supérieure ou égale à 9 heures
                            event.classNames = ['long-event']; // Appliquer une classe CSS spécifique
                        }
                        return event; // Retourner l'événement
                    });
                }
            },
            eventClassNames: function(arg) { // Personnaliser les classes CSS des événements
                const eventDuration = (new Date(arg.event.end) - new Date(arg.event.start)) / (1000 * 60 * 60); // Durée en heures
                if (eventDuration >= 9) { // Vérifier si la durée est supérieure ou égale à 9 heures
                    return ['long-event']; // Retourne la classe 'long-event' pour les événements de longue durée
                }
                return []; // Retourne un tableau vide pour les autres événements
            },
            eventContent: function(arg) { 
                // Personnaliser l'affichage des événements pour inclure l'heure de début et le titre
                let timeText = ''; // Initialiser le texte de l'heure
                if (arg.event.start) { // Vérifier si l'heure de début est précisée
                    const start = new Date(arg.event.start); // Convertir l'heure de début en objet Date
                    timeText = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); // Formater l'heure
                }
                return { html: `<div class="fc-event-time">${timeText}</div><div class="fc-event-title">${arg.event.title}</div>` }; // Retourner le contenu HTML
            },
            selectable: true, // Activer la sélection
            selectAllow: function(selectInfo) { // Autoriser la sélection
                const selectedDate = selectInfo.startStr.split('T')[0]; // Extraire la date sélectionnée

                // Vérifier si la date est passée ou est aujourd'hui
                const today = new Date().toISOString().split('T')[0];
                if (selectedDate <= today) {
                    return false; // Empêcher la sélection des dates passées ou d'aujourd'hui
                }

                if (reservedDates.includes(selectedDate)) { // Vérifier si la date est réservée
                    return false; // Empêcher la sélection
                }

                const period = selectInfo.start.getHours() < 12 ? 'morning' : 'afternoon'; // Déterminer la période

                if (reservedHalfDays[selectedDate] && reservedHalfDays[selectedDate].includes(period)) { // Vérifier si la demi-journée est réservée
                    return false; // Empêcher la sélection
                }

                return true; // Autoriser la sélection
            },
            selectOverlap: function(event) { // Gérer le chevauchement de la sélection
                return event.rendering === 'background'; // Autoriser le chevauchement pour les événements de fond
            }
        });

        calendar.render(); // Afficher le calendrier
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
            hiddenDateInput.value = date + " 08:00:00"; 
            form.appendChild(hiddenDateInput); 

            // Initialiser les champs
            const periodField = document.getElementById('booking_period').closest('.mb-3'); 
            const productField = document.getElementById('booking_product'); 
            const nbrParticipantField = document.getElementById('booking_nbrParticipant'); 
            const participantList = document.getElementById('participant-list'); 
            const addParticipantButton = document.getElementById('add-participant'); 
            let index = participantList.children.length;

            // Fonction pour basculer la visibilité du champ de période
            function togglePeriodField() { 
                if (parseInt(productField.value) === 3) { 
                    periodField.style.display = ''; 
                } else {
                    periodField.style.display = 'none'; 
                }
            }

            togglePeriodField(); 
            productField.addEventListener('change', togglePeriodField);

            if (parseInt(productField.value) === 3) { 
                const periodSelect = document.getElementById('booking_period');
                if (!periodSelect.value) { 
                    periodSelect.value = 'morning'; 
                }
            }

            // Récupérer les informations de l'utilisateur à partir des attributs data
            const userName = document.getElementById('participantsContainer').dataset.userName;
            const userFirstname = document.getElementById('participantsContainer').dataset.userFirstname;
            const userEmail = document.getElementById('participantsContainer').dataset.userEmail;

            // Fonction pour ajouter des champs de participants
            function addParticipantFields(count) {
                while (participantList.children.length < count) {
                    let newLi = document.createElement('li');
                    let newWidget = participantList.dataset.prototype.replace(/__name__/g, index++);
                    newLi.innerHTML = newWidget;

                    // N'ajouter le bouton "Supprimer" qu'aux participants autres que le premier
                    if (participantList.children.length > 0) {
                        newLi.innerHTML += '<button type="button" class="remove-participant btn btn-link text-decoration-none text-danger fw-bold">Supprimer <i class="bi bi-trash-fill"></i></button>';
                    }

                    participantList.appendChild(newLi);
                }
            }

            // Ajouter les 6 participants minimum dès le départ
            addParticipantFields(6);

            // Pré-remplir le participant 1 avec les informations de l'utilisateur
            const nameInput = document.getElementById('booking_participants_0_name'); 
            const emailInput = document.getElementById('booking_participants_0_email');
            const isNotifiedInput = document.getElementById('booking_participants_0_isNotified')

            // Cible les labels associés
            const nameLabel = document.querySelector('label[for="booking_participants_0_name"]');
            const emailLabel = document.querySelector('label[for="booking_participants_0_email"]');
            const isNotifiedLabel = document.querySelector('label[for="booking_participants_0_isNotified"]');
            if (nameInput) {
                nameInput.value = `${userFirstname} ${userName}`; 
                nameInput.readOnly = true; 
                nameInput.type = 'hidden'; 
                if (nameLabel) nameLabel.style.display = 'none'; 
            }

            if (emailInput) {
                emailInput.value = userEmail;
                emailInput.readOnly = true; 
                emailInput.type = 'hidden'; 
                if (emailLabel) emailLabel.style.display = 'none'; 
            }

            if (isNotifiedInput) {
                isNotifiedInput.value = 0;
                isNotifiedInput.readOnly = true;
                isNotifiedInput.type = 'hidden';
                if (isNotifiedLabel) isNotifiedLabel.style.display = 'none';
            }

            // Fonction pour ajuster le nombre de participants
            function adjustParticipants() {
                const count = parseInt(nbrParticipantField.value);
                if (count >= 6) {
                    addParticipantFields(count);
                    // Supprimer les participants supplémentaires si le nombre de participants est réduit
                    while (participantList.children.length > count) {
                        participantList.removeChild(participantList.lastChild);
                    }
                }
            }

            // Mettre à jour les participants à chaque changement du nombre de participants
            nbrParticipantField.addEventListener('change', adjustParticipants);

            // Ajouter un participant manuellement (via le bouton "Ajouter un participant")
            addParticipantButton.addEventListener('click', () => {
                let newLi = document.createElement('li');
                let newWidget = participantList.dataset.prototype.replace(/__name__/g, index++);
                newLi.innerHTML = newWidget + '<button type="button" class="remove-participant btn btn-link text-decoration-none text-danger fw-bold">Supprimer <i class="bi bi-trash-fill"></i></button>';
                participantList.appendChild(newLi);
            });

            // Supprimer un participant avec une condition pour ne pas descendre en dessous de 6
            participantList.addEventListener('click', (e) => {
                if (e.target && e.target.classList.contains('remove-participant')) {
                    if (participantList.children.length > 6) {
                        e.target.parentElement.remove();
                        index--; 
                    } else {
                        alert("Vous ne pouvez pas supprimer des participants en dessous de 6.");
                    }
                }
            });

            // Vérification avant la soumission du formulaire avec récapitulatif
            form.addEventListener('submit', function(event) {
                event.preventDefault(); 

                let isValid = true;
                const participants = document.querySelectorAll('#participant-list li');

                participants.forEach((participant, index) => {
                    const nameInput = participant.querySelector('input[name*="[name]"]');
                    const emailInput = participant.querySelector('input[name*="[email]"]');

                    if (index > 0) { // Ne pas vérifier l'organisateur
                        if (!nameInput.value || !emailInput.value) {
                            isValid = false;
                            nameInput.classList.add('is-invalid'); // Ajoute une classe d'erreur
                            emailInput.classList.add('is-invalid'); // Ajoute une classe d'erreur
                        } else {
                            nameInput.classList.remove('is-invalid'); // Supprime la classe d'erreur
                            emailInput.classList.remove('is-invalid'); // Supprime la classe d'erreur
                        }
                    }
                });

                if (!isValid) {
                    alert("Veuillez remplir tous les champs des participants.");
                    return; 
                }

                // Collecter les données du formulaire pour affichage du récapitulatif
                const productText = productField.options[productField.selectedIndex].text; // Récupérer le texte du produit sélectionné
                const nbrParticipantsValue = nbrParticipantField.value; // Récupérer le nombre de participants

                let recapContent = `
                    <p><strong>Produit : </strong>${productText}</p>
                    <p><strong>Nombre de participants : </strong>${nbrParticipantsValue}</p>
                `;

                const participantList = document.getElementById('participant-list');
                participantList.querySelectorAll('li').forEach((li, index) => {
                    const name = li.querySelector('input[name*="[name]"]').value;
                    const email = li.querySelector('input[name*="[email]"]').value;
                    recapContent += `<p><strong>Participant ${index + 1} : </strong> ${name}, ${email}</p>`;
                });

                // Afficher le récapitulatif dans la modale
                document.getElementById('recapContent').innerHTML = recapContent;
                document.getElementById('recapModal').style.display = 'block';

                // Gérer les actions sur la modale
                document.getElementById('confirmButton').onclick = function() {
                    // Soumettre le formulaire avec fetch au lieu de form.submit();
                    const formData = new FormData(form);

                    fetch('/book', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Une fois que la réservation est réussie, créer la session Stripe
                            fetch(`/create-checkout-session/${data.invoiceId}`)
                                .then(response => response.json())
                                .then(session => {
                                    if (session.error) {
                                        alert(session.error);
                                    } else {
                                        const stripe = Stripe('pk_test_51PNxdI2KzfchddbZVdS365NZwFLFYZSvHgwicMD0bFrw5zwlCT2w5eGMusV9MZCn8vyd4Yf3CeupElRl4hC9AWOl00PvJNIKxE');
                                        stripe.redirectToCheckout({ sessionId: session.id });
                                    }
                                })
                                .catch(error => console.warn('Erreur lors de la création de la session Stripe:', error));
                        } else {
                            alert("Une erreur est survenue lors de la réservation.");
                        }
                    })
                    .catch(error => console.warn('Erreur lors de la soumission du formulaire:', error));
                };

                document.getElementById('modifyButton').onclick = function() {
                    document.getElementById('recapModal').style.display = 'none'; // Cacher la modale pour modification
                };

                document.querySelector('#recapModal .close').onclick = function() {
                    document.getElementById('recapModal').style.display = 'none';
                };
            });
        })
        .catch(error => console.warn('Error fetching the form:', error)); 
}
// Fermer la modal
document.querySelector('.close').onclick = function() { // Ajouter un écouteur d'événement pour le bouton de fermeture
    document.getElementById('bookingModal').style.display = 'none';
};

// Fermer la modal en cliquant en dehors de celle-ci
window.onclick = function(event) { // Ajouter un écouteur d'événement pour le clic sur la fenêtre
    if (event.target === document.getElementById('bookingModal')) {
        document.getElementById('bookingModal').style.display = 'none';
    }
};

// Initialiser FullCalendar lors du chargement de la page
document.addEventListener('DOMContentLoaded', function() { // Ajouter un écouteur d'événement pour le chargement du DOM
    initializeCalendar();
});