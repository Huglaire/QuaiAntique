const API_URL = 'http://127.0.0.1:8000/api';

/**
 * Récupère le token JWT enregistré.
 *
 * @returns {string|null}
 */
function getToken() {
    return localStorage.getItem('jwt');
}

/**
 * Affiche un message général dans la page.
 *
 * @param {string} message
 * @param {string} type
 */
function displayMessage(message, type = 'info') {
    const messageContainer =
        document.getElementById('statistics-message');

    if (!messageContainer) {
        return;
    }

    messageContainer.textContent = message;
    messageContainer.className =
        `alert alert-${type}`;
}

/**
 * Masque le message général.
 */
function hideMessage() {
    const messageContainer =
        document.getElementById('statistics-message');

    if (!messageContainer) {
        return;
    }

    messageContainer.textContent = '';
    messageContainer.className =
        'alert d-none';
}

/**
 * Affiche la moyenne de convives.
 *
 * @param {number|null} averageGuests
 */
function renderAverageGuests(averageGuests) {
    const container =
        document.getElementById('average-guests');

    if (!container) {
        return;
    }

    if (
        typeof averageGuests !== 'number'
        || !Number.isFinite(averageGuests)
    ) {
        container.textContent = '-';

        return;
    }

    container.textContent =
        averageGuests.toFixed(2);
}

/**
 * Affiche le taux d'occupation moyen par jour.
 *
 * @param {Object} occupancyByDay
 */
function renderOccupancyByDay(occupancyByDay) {
    const container =
        document.getElementById('occupancy-by-day');

    if (!container) {
        return;
    }

    container.replaceChildren();

    const dayNames = {
        monday: 'Lundi',
        tuesday: 'Mardi',
        wednesday: 'Mercredi',
        thursday: 'Jeudi',
        friday: 'Vendredi',
        saturday: 'Samedi',
        sunday: 'Dimanche'
    };

    Object.entries(dayNames).forEach(
        ([dayKey, dayLabel]) => {
            const row =
                document.createElement('div');

            row.classList.add(
                'd-flex',
                'justify-content-between',
                'align-items-center',
                'mb-2'
            );

            const label =
                document.createElement('span');

            label.textContent =
                dayLabel;

            const value =
                document.createElement('strong');

            const occupancy =
                occupancyByDay?.[dayKey];

            if (
                typeof occupancy === 'number'
                && Number.isFinite(occupancy)
            ) {
                value.textContent =
                    `${occupancy.toFixed(2)} %`;
            } else {
                value.textContent = '-';
            }

            row.append(
                label,
                value
            );

            container.append(row);
        }
    );
}

/**
 * Affiche le créneau le plus fréquenté.
 *
 * @param {Object} busiestTimeSlot
 */
function renderBusiestTimeSlot(busiestTimeSlot) {
    const timeContainer =
        document.getElementById('busiest-time');

    const guestsContainer =
        document.getElementById(
            'busiest-time-guests'
        );

    if (
        !timeContainer
        || !guestsContainer
    ) {
        return;
    }

    const time =
        busiestTimeSlot?.time;

    const guests =
        busiestTimeSlot?.guests;

    if (typeof time === 'string' && time) {
        timeContainer.textContent =
            time;
    } else {
        timeContainer.textContent =
            '-';
    }

    if (
        typeof guests === 'number'
        && Number.isFinite(guests)
    ) {
        guestsContainer.textContent =
            `${guests} convive${guests > 1 ? 's' : ''}`;
    } else {
        guestsContainer.textContent =
            '-';
    }
}

/**
 * Affiche la date de génération des statistiques.
 *
 * @param {string|null} generatedAt
 */
function renderGeneratedAt(generatedAt) {
    const container =
        document.getElementById(
            'statistics-generated-at'
        );

    if (!container) {
        return;
    }

    if (!generatedAt) {
        container.textContent = '';

        return;
    }

    const date =
        new Date(generatedAt);

    if (Number.isNaN(date.getTime())) {
        container.textContent = '';

        return;
    }

    container.textContent =
        `Statistiques générées le ${date.toLocaleString(
            'fr-FR'
        )}`;
}

/**
 * Affiche toutes les statistiques.
 *
 * @param {Object} statistics
 */
function renderStatistics(statistics) {
    renderAverageGuests(
        statistics.averageGuestsPerBooking
    );

    renderOccupancyByDay(
        statistics.averageOccupancyByDay
    );

    renderBusiestTimeSlot(
        statistics.busiestTimeSlot
    );

    renderGeneratedAt(
        statistics.generatedAt
    );
}

/**
 * Récupère les statistiques depuis l'API.
 *
 * @returns {Promise<void>}
 */
async function loadStatistics() {
    const token = getToken();

    if (!token) {
        displayMessage(
            'Vous devez être connecté pour accéder aux statistiques.',
            'danger'
        );

        return;
    }

    hideMessage();

    try {
        const response = await fetch(
            `${API_URL}/admin/statistics`,
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization':
                        `Bearer ${token}`
                }
            }
        );

        let data = null;

        try {
            data = await response.json();
        } catch {
            // La réponse ne contient pas de JSON exploitable.
        }

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
            displayMessage(
                data?.message
                || 'Impossible de récupérer les statistiques.',
                'danger'
            );

            return;
        }

        renderStatistics(data);

    } catch (error) {
        console.error(error);

        displayMessage(
            'Une erreur est survenue lors du chargement des statistiques.',
            'danger'
        );
    }
}

/**
 * Initialise la page des statistiques.
 *
 * @returns {Promise<void>}
 */
export async function init() {
    const messageContainer =
        document.getElementById(
            'statistics-message'
        );

    const averageGuests =
        document.getElementById(
            'average-guests'
        );

    const occupancyContainer =
        document.getElementById(
            'occupancy-by-day'
        );

    const busiestTime =
        document.getElementById(
            'busiest-time'
        );

    if (
        !messageContainer
        || !averageGuests
        || !occupancyContainer
        || !busiestTime
    ) {
        return;
    }

    await loadStatistics();
}