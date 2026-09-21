const API_URL = '/api';

/**
 * Récupère le token JWT enregistré.
 *
 * @returns {string|null}
 */
function getToken() {
    return localStorage.getItem('jwt');
}

/**
 * Affiche un message dans la zone prévue.
 *
 * @param {string} message
 * @param {string} type
 */
function displayMessage(message, type = 'info') {
    const messageContainer = document.getElementById(
        'bookings-message'
    );

    if (!messageContainer) {
        return;
    }

    messageContainer.textContent = message;
    messageContainer.className =
        `alert alert-${type}`;
}

/**
 * Masque le message affiché.
 */
function hideMessage() {
    const messageContainer = document.getElementById(
        'bookings-message'
    );

    if (!messageContainer) {
        return;
    }

    messageContainer.textContent = '';
    messageContainer.className =
        'alert d-none';
}

/**
 * Formate une date au format français.
 *
 * @param {string} date
 * @returns {string}
 */
function formatDate(date) {
    if (!date) {
        return 'Date inconnue';
    }

    const dateParts = date.split('-');

    if (dateParts.length !== 3) {
        return date;
    }

    return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
}

/**
 * Crée un élément de formulaire avec son label.
 *
 * @param {string} labelText
 * @param {string} inputId
 * @param {string} type
 * @param {string} value
 * @returns {HTMLElement}
 */
function createFormField(
    labelText,
    inputId,
    type,
    value
) {
    const wrapper = document.createElement('div');

    wrapper.classList.add('mb-3');

    const label = document.createElement('label');

    label.classList.add('form-label');
    label.setAttribute('for', inputId);
    label.textContent = labelText;

    const input = document.createElement('input');

    input.classList.add('form-control');
    input.id = inputId;
    input.type = type;
    input.value = value ?? '';

    wrapper.append(
        label,
        input
    );

    return wrapper;
}

/**
 * Crée le formulaire de modification d'une réservation.
 *
 * @param {Object} booking
 * @param {HTMLElement} cardBody
 */
function createEditForm(booking, cardBody) {
    const form = document.createElement('form');

    form.classList.add(
        'mt-4',
        'border-top',
        'pt-4'
    );

    const title = document.createElement('h4');

    title.classList.add(
        'h5',
        'mb-3'
    );

    title.textContent =
        'Modifier la réservation';

    const dateField = createFormField(
        'Date',
        `edit-date-${booking.uuid}`,
        'date',
        booking.bookingDate
    );

    const timeField = createFormField(
        'Heure',
        `edit-time-${booking.uuid}`,
        'time',
        booking.bookingTime
    );

    const guestsField = createFormField(
        'Nombre de personnes',
        `edit-guests-${booking.uuid}`,
        'number',
        booking.guestNumber
    );

    const allergyField = createFormField(
        'Allergies',
        `edit-allergy-${booking.uuid}`,
        'text',
        booking.allergy ?? ''
    );

    const buttonsContainer =
        document.createElement('div');

    buttonsContainer.classList.add(
        'd-flex',
        'gap-2',
        'flex-wrap'
    );

    const saveButton = document.createElement(
        'button'
    );

    saveButton.type = 'submit';
    saveButton.classList.add(
        'btn',
        'btn-primary'
    );
    saveButton.textContent =
        'Enregistrer les modifications';

    const cancelButton =
        document.createElement('button');

    cancelButton.type = 'button';
    cancelButton.classList.add(
        'btn',
        'btn-secondary'
    );
    cancelButton.textContent = 'Annuler';

    buttonsContainer.append(
        saveButton,
        cancelButton
    );

    form.append(
        title,
        dateField,
        timeField,
        guestsField,
        allergyField,
        buttonsContainer
    );

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            await updateBooking(
                booking.uuid,
                dateField.querySelector('input').value,
                timeField.querySelector('input').value,
                guestsField.querySelector('input').value,
                allergyField.querySelector('input').value
            );
        }
    );

    cancelButton.addEventListener(
        'click',
        () => {
            form.remove();
        }
    );

    cardBody.append(form);
}

/**
 * Crée une carte représentant une réservation.
 *
 * @param {Object} booking
 * @returns {HTMLElement}
 */
function createBookingCard(booking) {
    const column = document.createElement('div');

    column.classList.add(
        'col-12',
        'col-lg-6'
    );

    const card = document.createElement('article');

    card.classList.add(
        'card',
        'h-100',
        'shadow-sm'
    );

    const cardBody = document.createElement('div');

    cardBody.classList.add('card-body');

    const title = document.createElement('h3');

    title.classList.add(
        'card-title',
        'h5'
    );

    const user = booking.user;

    const firstName = user?.firstName ?? '';
    const lastName = user?.lastName ?? '';

    const fullName =
        `${firstName} ${lastName}`.trim();

    title.textContent =
        fullName || 'Client inconnu';

    const dateParagraph =
        document.createElement('p');

    dateParagraph.classList.add('mb-2');

    const dateStrong =
        document.createElement('strong');

    dateStrong.textContent = 'Date : ';

    const dateText = document.createTextNode(
        formatDate(booking.bookingDate)
    );

    dateParagraph.append(
        dateStrong,
        dateText
    );

    const timeParagraph =
        document.createElement('p');

    timeParagraph.classList.add('mb-2');

    const timeStrong =
        document.createElement('strong');

    timeStrong.textContent = 'Heure : ';

    const timeText = document.createTextNode(
        booking.bookingTime ?? 'Heure inconnue'
    );

    timeParagraph.append(
        timeStrong,
        timeText
    );

    const guestsParagraph =
        document.createElement('p');

    guestsParagraph.classList.add('mb-2');

    const guestsStrong =
        document.createElement('strong');

    guestsStrong.textContent =
        'Nombre de personnes : ';

    const guestsText = document.createTextNode(
        String(booking.guestNumber ?? 0)
    );

    guestsParagraph.append(
        guestsStrong,
        guestsText
    );

    const emailParagraph =
        document.createElement('p');

    emailParagraph.classList.add('mb-2');

    const emailStrong =
        document.createElement('strong');

    emailStrong.textContent = 'E-mail : ';

    const emailText = document.createTextNode(
        user?.email ?? 'E-mail inconnu'
    );

    emailParagraph.append(
        emailStrong,
        emailText
    );

    const allergyParagraph =
        document.createElement('p');

    allergyParagraph.classList.add('mb-3');

    const allergyStrong =
        document.createElement('strong');

    allergyStrong.textContent = 'Allergies : ';

    const allergyText = document.createTextNode(
        booking.allergy || 'Aucune'
    );

    allergyParagraph.append(
        allergyStrong,
        allergyText
    );

    const actionsContainer =
        document.createElement('div');

    actionsContainer.classList.add(
        'd-flex',
        'gap-2',
        'flex-wrap'
    );

    const editButton =
        document.createElement('button');

    editButton.type = 'button';
    editButton.classList.add(
        'btn',
        'btn-primary'
    );
    editButton.textContent = 'Modifier';

    editButton.addEventListener(
        'click',
        () => {
            const existingForm =
                cardBody.querySelector('form');

            if (existingForm) {
                existingForm.remove();

                return;
            }

            createEditForm(
                booking,
                cardBody
            );
        }
    );

    const deleteButton =
        document.createElement('button');

    deleteButton.type = 'button';
    deleteButton.classList.add(
        'btn',
        'btn-danger'
    );
    deleteButton.textContent = 'Supprimer';

    deleteButton.addEventListener(
        'click',
        async () => {
            const clientName =
                fullName || 'ce client';

            const confirmed = window.confirm(
                `Voulez-vous vraiment supprimer la réservation de ${clientName} du ${formatDate(booking.bookingDate)} à ${booking.bookingTime} ?`
            );

            if (!confirmed) {
                return;
            }

            await deleteBooking(booking.uuid);
        }
    );

    actionsContainer.append(
        editButton,
        deleteButton
    );

    cardBody.append(
        title,
        dateParagraph,
        timeParagraph,
        guestsParagraph,
        emailParagraph,
        allergyParagraph,
        actionsContainer
    );

    card.append(cardBody);
    column.append(card);

    return column;
}

/**
 * Affiche les réservations dans la page.
 *
 * @param {Array} bookings
 */
function renderBookings(bookings) {
    const container = document.getElementById(
        'bookings-container'
    );

    if (!container) {
        return;
    }

    container.replaceChildren();

    if (
        !Array.isArray(bookings) ||
        bookings.length === 0
    ) {
        displayMessage(
            'Aucune réservation pour cette date.',
            'info'
        );

        return;
    }

    hideMessage();

    const fragment =
        document.createDocumentFragment();

    bookings.forEach((booking) => {
        fragment.append(
            createBookingCard(booking)
        );
    });

    container.append(fragment);
}

/**
 * Récupère les réservations administrateur.
 *
 * @param {string|null} date
 */
async function loadBookings(date = null) {
    const token = getToken();

    if (!token) {
        displayMessage(
            'Vous devez être connecté pour accéder aux réservations.',
            'danger'
        );

        return;
    }

    const container = document.getElementById(
        'bookings-container'
    );

    if (container) {
        container.replaceChildren();
    }

    hideMessage();

    const url = new URL(
        `${API_URL}/admin/bookings`,
        window.location.origin
    );

    if (date) {
        url.searchParams.set(
            'date',
            date
        );
    }

    try {
        const response = await fetch(
            url.toString(),
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            }
        );

        if (response.status === 401) {
            displayMessage(
                'Votre session a expiré. Veuillez vous reconnecter.',
                'danger'
            );

            return;
        }

        if (response.status === 403) {
            displayMessage(
                'Accès réservé aux administrateurs.',
                'danger'
            );

            return;
        }

        if (!response.ok) {
            throw new Error(
                'Impossible de récupérer les réservations.'
            );
        }

        const bookings = await response.json();

        renderBookings(bookings);

    } catch (error) {
        console.error(error);

        displayMessage(
            'Une erreur est survenue lors du chargement des réservations.',
            'danger'
        );
    }
}

/**
 * Modifie une réservation administrateur.
 *
 * @param {string} uuid
 * @param {string} bookingDate
 * @param {string} bookingTime
 * @param {string} guestNumber
 * @param {string} allergy
 */
async function updateBooking(
    uuid,
    bookingDate,
    bookingTime,
    guestNumber,
    allergy
) {
    const token = getToken();

    if (!token) {
        displayMessage(
            'Vous devez être connecté.',
            'danger'
        );

        return;
    }

    if (
        !bookingDate ||
        !bookingTime ||
        !guestNumber
    ) {
        displayMessage(
            'Veuillez remplir tous les champs obligatoires.',
            'danger'
        );

        return;
    }

    const guestNumberValue =
        Number(guestNumber);

    if (
        !Number.isInteger(guestNumberValue) ||
        guestNumberValue < 1
    ) {
        displayMessage(
            'Le nombre de personnes doit être un nombre entier supérieur à zéro.',
            'danger'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/bookings/${uuid}`,
            {
                method: 'PATCH',
                headers: {
                    'Content-Type':
                        'application/json',
                    'Accept':
                        'application/json',
                    'Authorization':
                        `Bearer ${token}`
                },
                body: JSON.stringify({
                    bookingDate,
                    bookingTime,
                    guestNumber:
                        guestNumberValue,
                    allergy: allergy.trim()
                })
            }
        );

        if (response.status === 401) {
            displayMessage(
                'Votre session a expiré. Veuillez vous reconnecter.',
                'danger'
            );

            return;
        }

        if (response.status === 403) {
            displayMessage(
                'Accès réservé aux administrateurs.',
                'danger'
            );

            return;
        }

        if (!response.ok) {
            let errorMessage =
                'Impossible de modifier la réservation.';

            try {
                const data =
                    await response.json();

                if (data.message) {
                    errorMessage =
                        data.message;
                }
            } catch {
                // Aucun message JSON exploitable
            }

            displayMessage(
                errorMessage,
                'danger'
            );

            return;
        }

        displayMessage(
            'La réservation a été modifiée avec succès.',
            'success'
        );

        const dateInput =
            document.getElementById('booking-date');

        const selectedDate =
            dateInput?.value || null;

        await loadBookings(selectedDate);

    } catch (error) {
        console.error(error);

        displayMessage(
            'Une erreur est survenue lors de la modification de la réservation.',
            'danger'
        );
    }
}

/**
 * Supprime une réservation administrateur.
 *
 * @param {string} uuid
 */
async function deleteBooking(uuid) {
    const token = getToken();

    if (!token) {
        displayMessage(
            'Vous devez être connecté.',
            'danger'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/bookings/${uuid}`,
            {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Authorization':
                        `Bearer ${token}`
                }
            }
        );

        if (response.status === 401) {
            displayMessage(
                'Votre session a expiré. Veuillez vous reconnecter.',
                'danger'
            );

            return;
        }

        if (response.status === 403) {
            displayMessage(
                'Accès réservé aux administrateurs.',
                'danger'
            );

            return;
        }

        if (!response.ok) {
            let errorMessage =
                'Impossible de supprimer la réservation.';

            try {
                const data =
                    await response.json();

                if (data.message) {
                    errorMessage =
                        data.message;
                }
            } catch {
                // Aucun message JSON exploitable
            }

            displayMessage(
                errorMessage,
                'danger'
            );

            return;
        }

        displayMessage(
            'La réservation a été supprimée avec succès.',
            'success'
        );

        const dateInput =
            document.getElementById('booking-date');

        const selectedDate =
            dateInput?.value || null;

        await loadBookings(selectedDate);

    } catch (error) {
        console.error(error);

        displayMessage(
            'Une erreur est survenue lors de la suppression de la réservation.',
            'danger'
        );
    }
}

/**
 * Initialise la page de gestion des réservations.
 */
export async function init() {
    const dateInput =
        document.getElementById('booking-date');

    const resetButton =
        document.getElementById('reset-date');

    if (!dateInput || !resetButton) {
        return;
    }

    dateInput.addEventListener(
        'change',
        () => {
            const selectedDate =
                dateInput.value;

            if (!selectedDate) {
                loadBookings();

                return;
            }

            loadBookings(selectedDate);
        }
    );

    resetButton.addEventListener(
        'click',
        () => {
            dateInput.value = '';

            loadBookings();
        }
    );

    await loadBookings();
}