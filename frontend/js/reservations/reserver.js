import {
    getCurrentUser,
    getToken,
    isAuthenticated
} from '../script.js';


const API_URL = '/api';


/**
 * Affiche un message d'erreur.
 *
 * @param {string} message
 */
function showError(message) {
    const error = document.getElementById(
        'reservation-error'
    );

    if (!error) {
        return;
    }

    error.textContent = message;
    error.classList.remove('d-none');
}


/**
 * Masque le message d'erreur.
 */
function hideError() {
    const error = document.getElementById(
        'reservation-error'
    );

    if (!error) {
        return;
    }

    error.textContent = '';
    error.classList.add('d-none');
}


/**
 * Affiche un message de succès.
 *
 * @param {string} message
 */
function showSuccess(message) {
    const success = document.getElementById(
        'reservation-success'
    );

    if (!success) {
        return;
    }

    success.textContent = message;
    success.classList.remove('d-none');
}


/**
 * Masque le message de succès.
 */
function hideSuccess() {
    const success = document.getElementById(
        'reservation-success'
    );

    if (!success) {
        return;
    }

    success.textContent = '';
    success.classList.add('d-none');
}


/**
 * Vérifie si une date correspond à un dimanche.
 *
 * @param {string} dateString
 * @returns {boolean}
 */
function isSunday(dateString) {
    if (!dateString) {
        return false;
    }

    const dateParts =
        dateString.split('-');

    if (dateParts.length !== 3) {
        return false;
    }

    const year =
        Number(dateParts[0]);

    const month =
        Number(dateParts[1]) - 1;

    const day =
        Number(dateParts[2]);

    const date =
        new Date(
            year,
            month,
            day
        );

    return date.getDay() === 0;
}


/**
 * Affiche un message dans la zone des créneaux.
 *
 * @param {string} message
 * @param {string} className
 */
function displaySlotsMessage(
    message,
    className = 'text-muted'
) {
    const slotsContainer = document.getElementById(
        'reservation-slots'
    );

    if (!slotsContainer) {
        return;
    }

    const paragraph = document.createElement('p');

    paragraph.classList.add(
        className,
        'mb-0'
    );

    paragraph.textContent = message;

    slotsContainer.replaceChildren(
        paragraph
    );
}


/**
 * Crée un bouton correspondant à un créneau.
 *
 * @param {Object} slot
 * @param {string} service
 * @returns {HTMLButtonElement}
 */
function createSlotButton(slot, service) {
    const button = document.createElement('button');

    button.type = 'button';

    button.classList.add(
        'btn',
        'btn-outline-primary',
        'me-2',
        'mb-2'
    );

    button.textContent = slot.time;

    button.dataset.time =
        slot.time;

    button.dataset.service =
        service;

    if (!slot.available) {
        button.disabled = true;

        button.classList.add(
            'disabled'
        );

        button.title =
            'Ce créneau n’est plus disponible pour ce nombre de personnes.';
    }

    button.addEventListener(
        'click',
        () => {
            selectTimeSlot(button);
        }
    );

    return button;
}


/**
 * Sélectionne un créneau.
 *
 * @param {HTMLButtonElement} selectedButton
 */
function selectTimeSlot(selectedButton) {
    const slotsContainer = document.getElementById(
        'reservation-slots'
    );

    const timeInput = document.getElementById(
        'reservation-time'
    );

    if (
        !slotsContainer
        || !timeInput
    ) {
        return;
    }

    const buttons =
        slotsContainer.querySelectorAll(
            'button[data-time]'
        );

    buttons.forEach(
        (button) => {
            button.classList.remove(
                'active'
            );
        }
    );

    selectedButton.classList.add(
        'active'
    );

    timeInput.value =
        selectedButton.dataset.time;
}


/**
 * Crée une section de service contenant ses créneaux.
 *
 * @param {string} title
 * @param {Array} slots
 * @param {string} service
 * @returns {HTMLElement}
 */
function createServiceSection(
    title,
    slots,
    service
) {
    const section = document.createElement(
        'div'
    );

    section.classList.add(
        'mb-4'
    );

    const heading = document.createElement(
        'h4'
    );

    heading.classList.add(
        'h6',
        'mb-3'
    );

    heading.textContent =
        title;

    const slotsWrapper =
        document.createElement(
            'div'
        );

    slots.forEach(
        (slot) => {
            const button =
                createSlotButton(
                    slot,
                    service
                );

            slotsWrapper.append(
                button
            );
        }
    );

    section.append(
        heading,
        slotsWrapper
    );

    return section;
}


/**
 * Affiche les créneaux retournés par l'API.
 *
 * @param {Object} data
 */
function displaySlots(data) {
    const slotsContainer =
        document.getElementById(
            'reservation-slots'
        );

    const timeInput =
        document.getElementById(
            'reservation-time'
        );

    if (
        !slotsContainer
        || !timeInput
    ) {
        return;
    }

    timeInput.value = '';

    slotsContainer.replaceChildren();

    const hasLunch =
        Array.isArray(data.lunch)
        && data.lunch.length > 0;

    const hasDinner =
        Array.isArray(data.dinner)
        && data.dinner.length > 0;

    if (
        !hasLunch
        && !hasDinner
    ) {
        displaySlotsMessage(
            'Aucun créneau n’est disponible pour cette date.'
        );

        return;
    }

    if (hasLunch) {
        const lunchSection =
            createServiceSection(
                'Déjeuner',
                data.lunch,
                'lunch'
            );

        slotsContainer.append(
            lunchSection
        );
    }

    if (hasDinner) {
        const dinnerSection =
            createServiceSection(
                'Dîner',
                data.dinner,
                'dinner'
            );

        slotsContainer.append(
            dinnerSection
        );
    }
}


/**
 * Récupère les créneaux disponibles.
 */
async function loadAvailability() {
    const dateInput =
        document.getElementById(
            'reservation-date'
        );

    const guestNumberInput =
        document.getElementById(
            'reservation-guest-number'
        );

    if (
        !dateInput
        || !guestNumberInput
    ) {
        return;
    }

    const date =
        dateInput.value;

    const guestNumber =
        Number(
            guestNumberInput.value
        );

    const timeInput =
        document.getElementById(
            'reservation-time'
        );

    if (timeInput) {
        timeInput.value = '';
    }

    hideError();

    if (!date) {
        displaySlotsMessage(
            'Sélectionnez une date et un nombre de personnes.'
        );

        return;
    }

    if (isSunday(date)) {
        displaySlotsMessage(
            'Le restaurant est fermé le dimanche.',
            'text-danger'
        );

        showError(
            'Le restaurant est fermé le dimanche. Veuillez choisir une autre date.'
        );

        return;
    }

    if (!Number.isInteger(guestNumber)) {
        displaySlotsMessage(
            'Sélectionnez une date et un nombre de personnes.'
        );

        return;
    }

    if (
        guestNumber < 1
        || guestNumber > 50
    ) {
        displaySlotsMessage(
            'Le nombre de personnes doit être compris entre 1 et 50.',
            'text-danger'
        );

        return;
    }

    displaySlotsMessage(
        'Recherche des créneaux disponibles...'
    );

    try {
        const params =
            new URLSearchParams({
                date,
                guestNumber: String(
                    guestNumber
                )
            });

        const token =
            getToken();

        if (!token) {
            showError(
                'Votre session a expiré. Veuillez vous reconnecter.'
            );

            return;
        }

        const response =
            await fetch(
                `${API_URL}/bookings/availability?${params.toString()}`,
                {
                    method: 'GET',
                    headers: {
                        'Accept':
                            'application/json',
                        'Authorization':
                            `Bearer ${token}`
                    }
                }
            );

        let data = {};

        try {
            data =
                await response.json();
        } catch {
            data = {};
        }

        if (!response.ok) {
            if (response.status === 401) {
                showError(
                    'Votre session a expiré. Veuillez vous reconnecter.'
                );

                return;
            }

            throw new Error(
                data.message
                ?? 'Impossible de récupérer les créneaux disponibles.'
            );
        }

        displaySlots(
            data
        );

    } catch (error) {
        displaySlotsMessage(
            'Impossible de récupérer les créneaux disponibles.',
            'text-danger'
        );

        showError(
            error.message
            ?? 'Une erreur est survenue lors de la récupération des disponibilités.'
        );
    }
}


/**
 * Préremplit le formulaire avec les informations du compte.
 *
 * @param {Object} user
 */
function displayUserInformation(user) {
    const guestNumberInput =
        document.getElementById(
            'reservation-guest-number'
        );

    const allergyInput =
        document.getElementById(
            'reservation-allergy'
        );

    if (guestNumberInput) {
        guestNumberInput.value =
            user.guestNumber ?? '';
    }

    if (allergyInput) {
        allergyInput.value =
            user.allergy ?? '';
    }
}


/**
 * Définit la date minimale sélectionnable.
 */
function setMinimumDate() {
    const dateInput =
        document.getElementById(
            'reservation-date'
        );

    if (!dateInput) {
        return;
    }

    const today =
        new Date();

    const year =
        today.getFullYear();

    const month =
        String(
            today.getMonth() + 1
        ).padStart(
            2,
            '0'
        );

    const day =
        String(
            today.getDate()
        ).padStart(
            2,
            '0'
        );

    const todayString =
        `${year}-${month}-${day}`;

    dateInput.min =
        todayString;

    if (!dateInput.value) {
        dateInput.value =
            todayString;
    }
}


/**
 * Initialise les changements de date et de nombre de personnes.
 */
function initAvailabilityEvents() {
    const dateInput =
        document.getElementById(
            'reservation-date'
        );

    const guestNumberInput =
        document.getElementById(
            'reservation-guest-number'
        );

    if (dateInput) {
        dateInput.addEventListener(
            'change',
            loadAvailability
        );
    }

    if (guestNumberInput) {
        guestNumberInput.addEventListener(
            'change',
            loadAvailability
        );
    }
}


/**
 * Initialise l'envoi du formulaire.
 */
function initReservationForm() {
    const form =
        document.getElementById(
            'reservation-form'
        );

    if (!form) {
        return;
    }

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            hideError();
            hideSuccess();

            const guestNumberInput =
                document.getElementById(
                    'reservation-guest-number'
                );

            const dateInput =
                document.getElementById(
                    'reservation-date'
                );

            const timeInput =
                document.getElementById(
                    'reservation-time'
                );

            const allergyInput =
                document.getElementById(
                    'reservation-allergy'
                );

            if (
                !guestNumberInput
                || !dateInput
                || !timeInput
                || !allergyInput
            ) {
                return;
            }

            const guestNumber =
                Number(
                    guestNumberInput.value
                );

            const bookingDate =
                dateInput.value;

            const bookingTime =
                timeInput.value;

            const allergy =
                allergyInput.value.trim();

            if (
                !Number.isInteger(
                    guestNumber
                )
                || guestNumber < 1
                || guestNumber > 50
            ) {
                showError(
                    'Le nombre de personnes doit être compris entre 1 et 50.'
                );

                return;
            }

            if (!bookingDate) {
                showError(
                    'Veuillez sélectionner une date.'
                );

                return;
            }

            if (isSunday(bookingDate)) {
                showError(
                    'Le restaurant est fermé le dimanche. Veuillez choisir une autre date.'
                );

                displaySlotsMessage(
                    'Le restaurant est fermé le dimanche.',
                    'text-danger'
                );

                timeInput.value = '';

                return;
            }

            if (!bookingTime) {
                showError(
                    'Veuillez sélectionner un créneau horaire.'
                );

                return;
            }

            const token =
                getToken();

            if (!token) {
                showError(
                    'Votre session a expiré. Veuillez vous reconnecter.'
                );

                return;
            }

            try {
                const response =
                    await fetch(
                        `${API_URL}/bookings`,
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type':
                                    'application/json',
                                'Accept':
                                    'application/json',
                                'Authorization':
                                    `Bearer ${token}`
                            },
                            body: JSON.stringify({
                                guestNumber,
                                bookingDate,
                                bookingTime,
                                allergy:
                                    allergy || null
                            })
                        }
                    );

                let data = {};

                try {
                    data =
                        await response.json();
                } catch {
                    data = {};
                }

                if (!response.ok) {
                    if (
                        response.status === 401
                    ) {
                        showError(
                            'Votre session a expiré. Veuillez vous reconnecter.'
                        );

                        return;
                    }

                    throw new Error(
                        data.message
                        ?? 'Impossible de créer la réservation.'
                    );
                }

                showSuccess(
                    'Votre réservation a été créée avec succès.'
                );

                timeInput.value = '';

                const selectedButtons =
                    document.querySelectorAll(
                        '#reservation-slots button.active'
                    );

                selectedButtons.forEach(
                    (button) => {
                        button.classList.remove(
                            'active'
                        );
                    }
                );

                await loadAvailability();

            } catch (error) {
                showError(
                    error.message
                    ?? 'Une erreur est survenue lors de la création de la réservation.'
                );
            }
        }
    );
}


/**
 * Initialise la page de réservation.
 */
export async function init() {
    hideError();
    hideSuccess();

    if (!isAuthenticated()) {
        showError(
            'Vous devez être connecté pour réserver une table.'
        );

        return;
    }

    setMinimumDate();

    const user =
        await getCurrentUser();

    if (!user) {
        showError(
            'Impossible de récupérer les informations de votre compte.'
        );

        return;
    }

    displayUserInformation(
        user
    );

    initAvailabilityEvents();

    initReservationForm();

    await loadAvailability();
}