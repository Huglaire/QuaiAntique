import {
    getToken,
    isAuthenticated
} from '../script.js';


const API_URL = 'http://127.0.0.1:8000/api';


/**
 * Affiche un message d'erreur.
 *
 * @param {string} message
 */
function showError(message) {
    const error = document.getElementById(
        'bookings-error'
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
        'bookings-error'
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
        'bookings-success'
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
        'bookings-success'
    );

    if (!success) {
        return;
    }

    success.textContent = '';
    success.classList.add('d-none');
}


/**
 * Formate une date au format français.
 *
 * @param {string|null} date
 * @returns {string}
 */
function formatDate(date) {
    if (!date) {
        return 'Date inconnue';
    }

    const dateParts =
        date.split('-');

    if (dateParts.length !== 3) {
        return date;
    }

    return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
}


/**
 * Crée un élément de texte avec un libellé.
 *
 * @param {string} label
 * @param {string} value
 * @returns {HTMLElement}
 */
function createInformationLine(
    label,
    value
) {
    const paragraph =
        document.createElement('p');

    paragraph.classList.add(
        'mb-2'
    );

    const strong =
        document.createElement('strong');

    strong.textContent =
        `${label} : `;

    const span =
        document.createElement('span');

    span.textContent =
        value;

    paragraph.append(
        strong,
        span
    );

    return paragraph;
}


/**
 * Crée le formulaire de modification d'une réservation.
 *
 * @param {Object} booking
 * @param {HTMLElement} container
 */
function createEditForm(
    booking,
    container
) {
    const form =
        document.createElement('form');

    form.classList.add(
        'mt-4',
        'border-top',
        'pt-4'
    );

    const heading =
        document.createElement('h4');

    heading.classList.add(
        'h5',
        'mb-3'
    );

    heading.textContent =
        'Modifier la réservation';

    form.append(
        heading
    );

    const fieldsRow =
        document.createElement('div');

    fieldsRow.classList.add(
        'row'
    );

    /*
     * Nombre de personnes.
     */
    const guestColumn =
        document.createElement('div');

    guestColumn.classList.add(
        'col-12',
        'col-md-4',
        'mb-3'
    );

    const guestLabel =
        document.createElement('label');

    guestLabel.classList.add(
        'form-label'
    );

    guestLabel.textContent =
        'Nombre de personnes';

    const guestInput =
        document.createElement('input');

    guestInput.type = 'number';
    guestInput.classList.add(
        'form-control'
    );
    guestInput.min = '1';
    guestInput.max = '50';
    guestInput.required = true;
    guestInput.value =
        booking.guestNumber ?? '';

    guestColumn.append(
        guestLabel,
        guestInput
    );

    /*
     * Date.
     */
    const dateColumn =
        document.createElement('div');

    dateColumn.classList.add(
        'col-12',
        'col-md-4',
        'mb-3'
    );

    const dateLabel =
        document.createElement('label');

    dateLabel.classList.add(
        'form-label'
    );

    dateLabel.textContent =
        'Date';

    const dateInput =
        document.createElement('input');

    dateInput.type = 'date';
    dateInput.classList.add(
        'form-control'
    );
    dateInput.required = true;
    dateInput.value =
        booking.bookingDate ?? '';

    dateColumn.append(
        dateLabel,
        dateInput
    );

    /*
     * Heure.
     */
    const timeColumn =
        document.createElement('div');

    timeColumn.classList.add(
        'col-12',
        'col-md-4',
        'mb-3'
    );

    const timeLabel =
        document.createElement('label');

    timeLabel.classList.add(
        'form-label'
    );

    timeLabel.textContent =
        'Heure';

    const timeInput =
        document.createElement('input');

    timeInput.type = 'time';
    timeInput.classList.add(
        'form-control'
    );
    timeInput.required = true;
    timeInput.value =
        booking.bookingTime ?? '';

    timeColumn.append(
        timeLabel,
        timeInput
    );

    fieldsRow.append(
        guestColumn,
        dateColumn,
        timeColumn
    );

    /*
     * Allergies.
     */
    const allergyGroup =
        document.createElement('div');

    allergyGroup.classList.add(
        'mb-3'
    );

    const allergyLabel =
        document.createElement('label');

    allergyLabel.classList.add(
        'form-label'
    );

    allergyLabel.textContent =
        'Allergies ou informations alimentaires';

    const allergyInput =
        document.createElement('textarea');

    allergyInput.classList.add(
        'form-control'
    );

    allergyInput.rows = 3;

    allergyInput.value =
        booking.allergy ?? '';

    allergyGroup.append(
        allergyLabel,
        allergyInput
    );

    /*
     * Boutons.
     */
    const buttonsWrapper =
        document.createElement('div');

    buttonsWrapper.classList.add(
        'd-flex',
        'flex-wrap',
        'gap-2'
    );

    const saveButton =
        document.createElement('button');

    saveButton.type = 'submit';

    saveButton.classList.add(
        'btn',
        'btn-primary'
    );

    saveButton.textContent =
        'Enregistrer';

    const cancelButton =
        document.createElement('button');

    cancelButton.type = 'button';

    cancelButton.classList.add(
        'btn',
        'btn-outline-secondary'
    );

    cancelButton.textContent =
        'Annuler';

    cancelButton.addEventListener(
        'click',
        () => {
            form.remove();
        }
    );

    buttonsWrapper.append(
        saveButton,
        cancelButton
    );

    form.append(
        fieldsRow,
        allergyGroup,
        buttonsWrapper
    );

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            hideError();
            hideSuccess();

            const guestNumber =
                Number(
                    guestInput.value
                );

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

            if (!dateInput.value) {
                showError(
                    'Veuillez sélectionner une date.'
                );

                return;
            }

            if (!timeInput.value) {
                showError(
                    'Veuillez sélectionner une heure.'
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

            saveButton.disabled = true;

            try {
                const response =
                    await fetch(
                        `${API_URL}/bookings/${booking.uuid}`,
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
                                guestNumber,
                                bookingDate:
                                    dateInput.value,
                                bookingTime:
                                    timeInput.value,
                                allergy:
                                    allergyInput.value.trim()
                                    || null
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
                        ?? 'Impossible de modifier la réservation.'
                    );
                }

                showSuccess(
                    'Votre réservation a été modifiée avec succès.'
                );

                await loadBookings();

            } catch (error) {
                showError(
                    error.message
                    ?? 'Une erreur est survenue lors de la modification.'
                );

            } finally {
                saveButton.disabled = false;
            }
        }
    );

    container.append(
        form
    );
}


/**
 * Supprime une réservation.
 *
 * @param {Object} booking
 */
async function deleteBooking(booking) {
    const confirmed =
        window.confirm(
            'Voulez-vous vraiment annuler cette réservation ?'
        );

    if (!confirmed) {
        return;
    }

    hideError();
    hideSuccess();

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
                `${API_URL}/bookings/${booking.uuid}`,
                {
                    method: 'DELETE',
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
                ?? 'Impossible d’annuler la réservation.'
            );
        }

        showSuccess(
            'Votre réservation a été annulée.'
        );

        await loadBookings();

    } catch (error) {
        showError(
            error.message
            ?? 'Une erreur est survenue lors de l’annulation.'
        );
    }
}


/**
 * Crée l'affichage d'une réservation.
 *
 * @param {Object} booking
 * @returns {HTMLElement}
 */
function createBookingCard(booking) {
    const article =
        document.createElement('article');

    article.classList.add(
        'border',
        'rounded',
        'p-4',
        'mb-4'
    );

    const heading =
        document.createElement('h3');

    heading.classList.add(
        'h5',
        'text-primary',
        'mb-3'
    );

    heading.textContent =
        `Réservation du ${formatDate(
            booking.bookingDate
        )}`;

    const information =
        document.createElement('div');

    information.append(
        createInformationLine(
            'Restaurant',
            booking.restaurant?.name
            ?? 'Quai Antique'
        ),
        createInformationLine(
            'Date',
            formatDate(
                booking.bookingDate
            )
        ),
        createInformationLine(
            'Heure',
            booking.bookingTime
            ?? 'Non renseignée'
        ),
        createInformationLine(
            'Nombre de personnes',
            String(
                booking.guestNumber
                ?? ''
            )
        ),
        createInformationLine(
            'Allergies',
            booking.allergy
            ?? 'Aucune'
        )
    );

    const buttons =
        document.createElement('div');

    buttons.classList.add(
        'd-flex',
        'flex-wrap',
        'gap-2',
        'mt-4'
    );

    const editButton =
        document.createElement('button');

    editButton.type = 'button';

    editButton.classList.add(
        'btn',
        'btn-outline-primary'
    );

    editButton.textContent =
        'Modifier';

    const deleteButton =
        document.createElement('button');

    deleteButton.type = 'button';

    deleteButton.classList.add(
        'btn',
        'btn-outline-danger'
    );

    deleteButton.textContent =
        'Annuler la réservation';

    editButton.addEventListener(
        'click',
        () => {
            const existingForm =
                article.querySelector(
                    'form'
                );

            if (existingForm) {
                existingForm.remove();

                return;
            }

            createEditForm(
                booking,
                article
            );
        }
    );

    deleteButton.addEventListener(
        'click',
        () => {
            deleteBooking(
                booking
            );
        }
    );

    buttons.append(
        editButton,
        deleteButton
    );

    article.append(
        heading,
        information,
        buttons
    );

    return article;
}


/**
 * Affiche le message lorsqu'aucune réservation n'existe.
 */
function displayEmptyState() {
    const container =
        document.getElementById(
            'bookings-list'
        );

    if (!container) {
        return;
    }

    const section =
        document.createElement('div');

    section.classList.add(
        'text-center',
        'py-4'
    );

    const paragraph =
        document.createElement('p');

    paragraph.classList.add(
        'text-muted',
        'mb-3'
    );

    paragraph.textContent =
        'Vous n’avez aucune réservation pour le moment.';

    const link =
        document.createElement('a');

    link.href =
        '/reservation';

    link.classList.add(
        'btn',
        'btn-primary'
    );

    link.textContent =
        'Réserver une table';

    section.append(
        paragraph,
        link
    );

    container.replaceChildren(
        section
    );
}


/**
 * Charge les réservations de l'utilisateur.
 */
async function loadBookings() {
    const container =
        document.getElementById(
            'bookings-list'
        );

    if (!container) {
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

    const loading =
        document.createElement('p');

    loading.classList.add(
        'text-muted'
    );

    loading.textContent =
        'Chargement de vos réservations...';

    container.replaceChildren(
        loading
    );

    try {
        const response =
            await fetch(
                `${API_URL}/bookings`,
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
                ?? 'Impossible de récupérer vos réservations.'
            );
        }

        const bookings =
            Array.isArray(data.bookings)
                ? data.bookings
                : [];

        if (bookings.length === 0) {
            displayEmptyState();

            return;
        }

        const fragment =
            document.createDocumentFragment();

        bookings.forEach(
            (booking) => {
                fragment.append(
                    createBookingCard(
                        booking
                    )
                );
            }
        );

        container.replaceChildren(
            fragment
        );

    } catch (error) {
        container.replaceChildren();

        showError(
            error.message
            ?? 'Une erreur est survenue lors de la récupération des réservations.'
        );
    }
}


/**
 * Initialise la page des réservations.
 */
export async function init() {
    hideError();
    hideSuccess();

    if (!isAuthenticated()) {
        showError(
            'Vous devez être connecté pour consulter vos réservations.'
        );

        return;
    }

    await loadBookings();
}