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
 * Affiche un message à l'administrateur.
 *
 * @param {string} message
 * @param {string} type
 */
function displayMessage(message, type = 'info') {
    const messageContainer = document.getElementById(
        'restaurant-message'
    );

    if (!messageContainer) {
        return;
    }

    messageContainer.textContent = message;
    messageContainer.className =
        `alert alert-${type}`;
}

/**
 * Récupère les informations du restaurant.
 *
 * @returns {Promise<Object|null>}
 */
async function loadRestaurant() {
    const token = getToken();

    if (!token) {
        displayMessage(
            'Vous devez être connecté pour accéder à cette page.',
            'danger'
        );

        return null;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/restaurant`,
            {
                method: 'GET',
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

            return null;
        }

        if (response.status === 403) {
            displayMessage(
                'Accès réservé aux administrateurs.',
                'danger'
            );

            return null;
        }

        if (!response.ok) {
            throw new Error(
                'Impossible de récupérer les informations du restaurant.'
            );
        }

        const restaurant =
            await response.json();

        const lunchOpeningInput =
            document.getElementById(
                'lunch-opening-time'
            );

        const lunchClosingInput =
            document.getElementById(
                'lunch-closing-time'
            );

        const dinnerOpeningInput =
            document.getElementById(
                'dinner-opening-time'
            );

        const dinnerClosingInput =
            document.getElementById(
                'dinner-closing-time'
            );

        const maxGuestsInput =
            document.getElementById('max-guests');

        if (lunchOpeningInput) {
            lunchOpeningInput.value =
                restaurant.lunchOpeningTime ?? '';
        }

        if (lunchClosingInput) {
            lunchClosingInput.value =
                restaurant.lunchClosingTime ?? '';
        }

        if (dinnerOpeningInput) {
            dinnerOpeningInput.value =
                restaurant.dinnerOpeningTime ?? '';
        }

        if (dinnerClosingInput) {
            dinnerClosingInput.value =
                restaurant.dinnerClosingTime ?? '';
        }

        if (maxGuestsInput) {
            maxGuestsInput.value =
                restaurant.maxGuest ?? '';
        }

        return restaurant;

    } catch (error) {
        console.error(error);

        displayMessage(
            'Une erreur est survenue lors du chargement des informations.',
            'danger'
        );

        return null;
    }
}

/**
 * Met à jour une information du restaurant.
 *
 * @param {Object} data
 * @param {string} successMessage
 */
async function updateRestaurant(
    data,
    successMessage
) {
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
            `${API_URL}/admin/restaurant`,
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
                body: JSON.stringify(data)
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
                'Impossible de modifier les informations.';

            try {
                const errorData =
                    await response.json();

                if (errorData.message) {
                    errorMessage =
                        errorData.message;
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
            successMessage,
            'success'
        );

    } catch (error) {
        console.error(error);

        displayMessage(
            'Une erreur est survenue lors de la modification.',
            'danger'
        );
    }
}

/**
 * Initialise la gestion des informations du restaurant.
 */
export async function init() {
    const lunchForm =
        document.getElementById('lunch-form');

    const dinnerForm =
        document.getElementById('dinner-form');

    const capacityForm =
        document.getElementById('capacity-form');

    if (
        !lunchForm ||
        !dinnerForm ||
        !capacityForm
    ) {
        return;
    }

    await loadRestaurant();

    lunchForm.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const lunchOpeningInput =
                document.getElementById(
                    'lunch-opening-time'
                );

            const lunchClosingInput =
                document.getElementById(
                    'lunch-closing-time'
                );

            if (
                !lunchOpeningInput?.value
                || !lunchClosingInput?.value
            ) {
                displayMessage(
                    'Veuillez renseigner les heures d’ouverture et de fermeture du service du midi.',
                    'danger'
                );

                return;
            }

            await updateRestaurant(
                {
                    lunchOpeningTime:
                        lunchOpeningInput.value,
                    lunchClosingTime:
                        lunchClosingInput.value
                },
                'Les horaires du service du midi ont été mis à jour.'
            );
        }
    );

    dinnerForm.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const dinnerOpeningInput =
                document.getElementById(
                    'dinner-opening-time'
                );

            const dinnerClosingInput =
                document.getElementById(
                    'dinner-closing-time'
                );

            if (
                !dinnerOpeningInput?.value
                || !dinnerClosingInput?.value
            ) {
                displayMessage(
                    'Veuillez renseigner les heures d’ouverture et de fermeture du service du soir.',
                    'danger'
                );

                return;
            }

            await updateRestaurant(
                {
                    dinnerOpeningTime:
                        dinnerOpeningInput.value,
                    dinnerClosingTime:
                        dinnerClosingInput.value
                },
                'Les horaires du service du soir ont été mis à jour.'
            );
        }
    );

    capacityForm.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const maxGuestsInput =
                document.getElementById('max-guests');

            const maxGuests =
                Number(maxGuestsInput?.value);

            if (
                !Number.isInteger(maxGuests) ||
                maxGuests < 1
            ) {
                displayMessage(
                    'Le nombre maximum de convives doit être un nombre entier supérieur à zéro.',
                    'danger'
                );

                return;
            }

            await updateRestaurant(
                {
                    maxGuest: maxGuests
                },
                'La capacité d’accueil a été mise à jour.'
            );
        }
    );
}