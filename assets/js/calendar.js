import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import '../styles/page/bookform.scss';
import '../styles/page/modal.scss';

// Classe pour gérer le calendrier
class CalendarManager {
    constructor() {
        this.calendar = null;
        this.reservedDates = [];
        this.reservedHalfDays = {};
        this.forfait = null;
        this.initializeCalendar();
    }

    // Fonction utilitaire pour obtenir toutes les dates entre deux dates
    static getDatesInRange(startDate, endDate) {
        const dates = [];
        let currentDate = new Date(startDate);
        const end = new Date(endDate);

        while (currentDate <= end) {
            dates.push(new Date(currentDate).toISOString().split('T')[0]);
            currentDate.setDate(currentDate.getDate() + 1);
        }

        return dates;
    }

    // Initialiser le calendrier
    initializeCalendar() {
        const calendarEl = document.getElementById('calendar');

        if (calendarEl) {
            this.forfait = calendarEl.dataset.forfait;

            this.calendar = new Calendar(calendarEl, {
                plugins: [dayGridPlugin, interactionPlugin],
                initialView: 'dayGridMonth',
                locale: frLocale,
                dateClick: this.handleDateClick.bind(this),
                events: (info, successCallback, failureCallback) => {
                    fetch('/bookings')
                        .then(response => response.json())
                        .then(data => {
                            this.handleEventsSuccess(data);
                            const unselectables = this.getUnselectableDates();
                            successCallback([...data, ...unselectables]);
                        })
                        .catch(() => {
                            alert('Il y a eu une erreur lors de la récupération des événements.');
                            failureCallback();
                        });
                },
                eventClassNames: this.getEventClassNames.bind(this),
                eventContent: this.getEventContent.bind(this),
                selectable: true,
                selectAllow: this.allowSelection.bind(this),
                selectOverlap: this.handleSelectOverlap.bind(this),
            });

            this.calendar.render();
        }
    }

    // Réintroduire la méthode getUnselectableDates() avec gestion des dates passées (1 an avant aujourd'hui jusqu'à hier)
    getUnselectableDates() {
        const dates = [];
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        const pastStart = new Date(today);
        pastStart.setFullYear(today.getFullYear() - 1); // Limite de 1 an en arrière

        // Ajouter toutes les dates passées (de pastStart à hier)
        let current = new Date(pastStart);
        while (current <= yesterday) {
            dates.push({
                start: current.toISOString().split('T')[0],
                display: 'background',
                overlap: false,
                classNames: ['fc-disabled-date', 'fc-disabled-strike'],
                title: "Ce jour n'est pas réservable. Un délai de 15 jours est requis pour l'organisation du bootcamp."
            });
            current.setDate(current.getDate() + 1);
        }

        // Ajouter les 14 prochains jours à partir d'aujourd'hui (inclus le 14e jour)
        for (let i = 0; i <= 14; i++) {
            const d = new Date(today);
            d.setDate(today.getDate() + i);
            dates.push({
                start: d.toISOString().split('T')[0],
                display: 'background',
                overlap: false,
                classNames: ['fc-disabled-date', 'fc-disabled-strike'],
                title: "Ce jour n'est pas réservable. Un délai de 15 jours est requis pour l'organisation du bootcamp."
            });
        }

        return dates;
    }

    // Gérer le clic sur une date
    handleDateClick(info) {
        // Blocage pour les réservations avant 15 jours
        const selectedDate = new Date(info.dateStr);
        const today = new Date();
        // Mettre les heures à 0 pour ignorer l'heure dans le calcul
        selectedDate.setHours(0,0,0,0);
        today.setHours(0,0,0,0);
        const diffInDays = (selectedDate - today) / (1000 * 60 * 60 * 24);
        if (diffInDays < 15) {
            alert("Les réservations doivent être effectuées au moins 15 jours à l'avance.");
            return;
        }

        const selectedDateStr = info.dateStr.split('T')[0];
        if (!this.reservedDates.includes(selectedDateStr) ||
            (this.reservedHalfDays[selectedDateStr] && this.reservedHalfDays[selectedDateStr].length < 2)) {
            new BookingFormManager(info.dateStr, this.forfait);
        }
    }

    // Gérer le succès de la récupération des événements
    handleEventsSuccess(data) {
        this.reservedDates = [];
        this.reservedHalfDays = {};

        data.forEach(event => {
            const eventDates = CalendarManager.getDatesInRange(event.start, event.end);
            this.reservedDates = this.reservedDates.concat(eventDates);

            if (event.period) {
                const eventDate = event.start.split('T')[0];
                if (!this.reservedHalfDays[eventDate]) {
                    this.reservedHalfDays[eventDate] = [];
                }
                this.reservedHalfDays[eventDate].push(event.period);
            }
        });

        return data.map(event => {
            const eventDuration = (new Date(event.end) - new Date(event.start)) / (1000 * 60 * 60);
            if (eventDuration >= 9) {
                event.classNames = ['long-event'];
            }
            return event;
        });
    }

    // Personnaliser les classes CSS des événements
    getEventClassNames(arg) {
        const eventDuration = (new Date(arg.event.end) - new Date(arg.event.start)) / (1000 * 60 * 60);
        return eventDuration >= 9 ? ['long-event'] : [];
    }

    // Personnaliser l'affichage des événements
    getEventContent(arg) {
        // Ne pas afficher de contenu pour les événements de fond (grisés/barrés)
        if (arg.event.display === 'background') {
            return {
                html: '',
            };
        }

        let timeText = '';
        if (arg.event.start) {
            const start = new Date(arg.event.start);
            timeText = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        return {
            html: `<div class="fc-event-time">${timeText}</div><div class="fc-event-title">${arg.event.title}</div>`,
        };
    }

    // Autoriser la sélection des dates disponibles
    allowSelection(selectInfo) {
        const selectedDate = selectInfo.startStr.split('T')[0];
        const today = new Date().toISOString().split('T')[0];

        if (selectedDate <= today || this.reservedDates.includes(selectedDate)) {
            return false;
        }

        const period = selectInfo.start.getHours() < 12 ? 'morning' : 'afternoon';

        if (this.reservedHalfDays[selectedDate] && this.reservedHalfDays[selectedDate].includes(period)) {
            return false;
        }

        return true;
    }

    // Gérer le chevauchement de la sélection
    handleSelectOverlap(event) {
        return event.rendering === 'background';
    }
}

// Classe pour gérer le formulaire de réservation
class BookingFormManager {
    constructor(date, forfait) {
        this.date = date;
        this.forfait = forfait;
        this.form = null;
        this.productPrices = {}; // Initialisation de la variable pour les prix des produits
        this.init();
    }

    // Initialiser le formulaire
    init() {
        fetch(`/booking/form?date=${this.date}&forfait=${this.forfait}`)
            .then(response => response.text())
            .then(html => {
                this.showBookingForm(html);
            })
            .catch(error => console.warn('Erreur lors de la récupération du formulaire:', error));
    }

    // Afficher le formulaire de réservation
    showBookingForm(html) {
        document.getElementById('bookingFormContainer').innerHTML = html;
        document.getElementById('bookingModal').style.display = 'block';

        this.form = document.querySelector('#bookingForm');
        this.addHiddenDateInput();
        this.initializeFields();
        this.addEventListeners();
        this.addModalEventListeners();
    }

    // Ajouter un champ caché pour la date
    addHiddenDateInput() {
        const hiddenDateInput = document.createElement('input');
        hiddenDateInput.type = 'hidden';
        hiddenDateInput.name = 'bookAt';
        hiddenDateInput.value = this.date + " 08:00:00";
        this.form.appendChild(hiddenDateInput);
    }

    // Initialiser les champs du formulaire
    initializeFields() {
        this.setupFormFields();
        this.prefillFirstParticipant();
    }

    // Configurer les champs du formulaire
    setupFormFields() {
        this.periodField = document.getElementById('booking_period').closest('.mb-3');
        this.productField = document.getElementById('booking_product');
        this.nbrParticipantField = document.getElementById('booking_nbrParticipant');
        this.participantList = document.getElementById('participant-list');
        this.addParticipantButton = document.getElementById('add-participant');
        this.index = this.participantList.children.length;

        // Récupérer les prix des produits depuis l'attribut data-product-prices
        const participantsContainer = document.getElementById('participantsContainer');
        this.productPrices = JSON.parse(participantsContainer.dataset.productPrices);

        this.togglePeriodField();
        this.productField.addEventListener('change', this.togglePeriodField.bind(this));

        if (parseInt(this.productField.value) === 3) {
            const periodSelect = document.getElementById('booking_period');
            if (!periodSelect.value) {
                periodSelect.value = 'morning';
            }
        }

        this.addParticipantFields(6);
    }

    // Basculer la visibilité du champ de période
    togglePeriodField() {
        this.periodField.style.display = parseInt(this.productField.value) === 3 ? '' : 'none';
    }

    // Pré-remplir le premier participant avec les informations de l'utilisateur
    prefillFirstParticipant() {
        const participantsContainer = document.getElementById('participantsContainer');
        const userName = participantsContainer.dataset.userName;
        const userFirstname = participantsContainer.dataset.userFirstname;
        const userEmail = participantsContainer.dataset.userEmail;

        const nameInput = document.getElementById('booking_participants_0_name');
        const emailInput = document.getElementById('booking_participants_0_email');
        const isNotifiedInput = document.getElementById('booking_participants_0_isNotified');

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
    }

    // Ajouter des champs pour les participants
    addParticipantFields(count) {
        while (this.participantList.children.length < count) {
            let newLi = document.createElement('li');
            let newWidget = this.participantList.dataset.prototype.replace(/__name__/g, this.index++);
            newLi.innerHTML = newWidget;

            if (this.participantList.children.length > 0) {
                newLi.innerHTML += '<button type="button" class="remove-participant btn btn-link text-decoration-none text-danger fw-bold">Supprimer <i class="bi bi-trash-fill"></i></button>';
            }

            this.participantList.appendChild(newLi);
        }
    }

    // Ajuster le nombre de participants
    adjustParticipants() {
        const count = parseInt(this.nbrParticipantField.value);
        if (count >= 6) {
            this.addParticipantFields(count);
            while (this.participantList.children.length > count) {
                this.participantList.removeChild(this.participantList.lastChild);
            }
        }
    }

    // Ajouter les écouteurs d'événements
    addEventListeners() {
        this.nbrParticipantField.addEventListener('change', this.adjustParticipants.bind(this));

        this.addParticipantButton.addEventListener('click', () => {
            let newLi = document.createElement('li');
            let newWidget = this.participantList.dataset.prototype.replace(/__name__/g, this.index++);
            newLi.innerHTML = newWidget + '<button type="button" class="remove-participant btn btn-link text-decoration-none text-danger fw-bold">Supprimer <i class="bi bi-trash-fill"></i></button>';
            this.participantList.appendChild(newLi);
        });

        this.participantList.addEventListener('click', (e) => {
            if (e.target && e.target.classList.contains('remove-participant')) {
                if (this.participantList.children.length > 6) {
                    e.target.parentElement.remove();
                    this.index--;
                } else {
                    alert("Vous ne pouvez pas supprimer des participants en dessous de 6.");
                }
            }
        });

        this.form.addEventListener('submit', this.handleSubmit.bind(this));
    }

    // Gérer la soumission du formulaire
    handleSubmit(event) {
        event.preventDefault();

        let isValid = true;
        const participants = document.querySelectorAll('#participant-list li');

        participants.forEach((participant, index) => {
            const nameInput = participant.querySelector('input[name*="[name]"]');
            const emailInput = participant.querySelector('input[name*="[email]"]');

            if (index > 0 && (!nameInput.value || !emailInput.value)) {
                isValid = false;
                nameInput.classList.add('is-invalid');
                emailInput.classList.add('is-invalid');
            } else {
                nameInput.classList.remove('is-invalid');
                emailInput.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            alert("Veuillez remplir tous les champs des participants.");
            return;
        }

        this.showRecapModal();
    }

    // Afficher la modale de récapitulatif
    showRecapModal() {
        const productText = this.productField.options[this.productField.selectedIndex].text;
        const nbrParticipantsValue = parseInt(this.nbrParticipantField.value);

        // Récupérer l'ID du produit sélectionné
        const selectedProductId = this.productField.value;
        // Récupérer le prix du produit à partir de this.productPrices
        const productPrice = parseFloat(this.productPrices[selectedProductId].price);
        // Calculer le total
        const totalPrice = productPrice * nbrParticipantsValue;

        let recapContent = `
            <p><strong>Produit : </strong>${productText}</p>
            <p><strong>Nombre de participants : </strong>${nbrParticipantsValue}</p>
            <p><strong>Total à payer : </strong>${totalPrice.toFixed(2)} €</p>
        `;

        // Ajouter les informations des participants
        this.participantList.querySelectorAll('li').forEach((li, index) => {
            const name = li.querySelector('input[name*="[name]"]')?.value || '';
            const email = li.querySelector('input[name*="[email]"]')?.value || '';
            recapContent += `<p><strong>Participant ${index + 1} : </strong> ${name}, ${email}</p>`;
        });

        document.getElementById('recapContent').innerHTML = recapContent;
        document.getElementById('recapModal').style.display = 'block';

        document.getElementById('confirmButton').onclick = this.handleConfirm.bind(this);
        document.getElementById('modifyButton').onclick = this.handleModify.bind(this);
        document.querySelector('#recapModal .close').onclick = this.handleCloseRecap.bind(this);
    }

    // Gérer la confirmation du récapitulatif
    handleConfirm() {
        const formData = new FormData(this.form);

        fetch('/book', {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetch(`/create-checkout-session/${data.invoiceId}`)
                        .then(response => response.json())
                        .then(session => {
                            if (session.error) {
                                alert(session.error);
                            } else {
                                const stripe = Stripe('pk_test_51OyW1HP5v3Vi9EzZYLzo6uXxIQdBmtkgqlJVcIFkqwwULoCUyaUlQaOA66xnD0XAbbPZmrFpNkRFT3GpS3fGSkHb00NuHnvrji');
                                stripe.redirectToCheckout({ sessionId: session.id });
                            }
                        })
                        .catch(error => console.warn('Erreur lors de la création de la session Stripe:', error));
                } else {
                    alert("Une erreur est survenue lors de la réservation.");
                }
            })
            .catch(error => console.warn('Erreur lors de la soumission du formulaire:', error));
    }

    // Gérer la modification depuis le récapitulatif
    handleModify() {
        document.getElementById('recapModal').style.display = 'none';
    }

    // Fermer la modale de récapitulatif
    handleCloseRecap() {
        document.getElementById('recapModal').style.display = 'none';
    }

    // Ajouter les écouteurs pour la modale
    addModalEventListeners() {
        document.querySelector('#bookingModal .close').onclick = this.closeModal.bind(this);

        window.onclick = (event) => {
            if (event.target === document.getElementById('bookingModal')) {
                this.closeModal();
            }
        };
    }

    // Fermer la modale de réservation
    closeModal() {
        document.getElementById('bookingModal').style.display = 'none';
    }
}

// Initialiser le gestionnaire de calendrier lors du chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    new CalendarManager();
});

// Ajout du style pour les dates non sélectionnables
const style = document.createElement('style');
style.innerHTML = `
.fc-disabled-date {
  background-color: #eee !important;
  pointer-events: none;
}
.fc-disabled-strike {
  position: relative;
}
.fc-disabled-strike::after {
  content: "";
  position: absolute;
  top: 50%;
  left: 5%;
  width: 90%;
  height: 2px;
  background-color: #999;
  transform: rotate(-15deg);
  z-index: 10;
  pointer-events: none;
}
`;
document.head.appendChild(style);