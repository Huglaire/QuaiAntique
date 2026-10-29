import {
    getCurrentUser,
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
        'account-error'
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
        'account-error'
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
        'account-success'
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
        'account-success'
    );

    if (!success) {
        return;
    }

    success.textContent = '';
    success.classList.add('d-none');
}


/**
 * Remplit le formulaire avec les informations de l'utilisateur.
 *
 * @param {Object} user
 */
function displayUser(user) {
    const firstName = document.getElementById(
        'account-first-name'
    );

    const lastName = document.getElementById(
        'account-last-name'
    );

    const email = document.getElementById(
        'account-email'
    );

    const guestNumber = document.getElementById(
        'account-guest-number'
    );

    const allergy = document.getElementById(
        'account-allergy'
    );

    if (
        !firstName
        || !lastName
        || !email
        || !guestNumber
        || !allergy
    ) {
        return;
    }

    firstName.value =
        user.firstName ?? '';

    lastName.value =
        user.lastName ?? '';

    email.value =
        user.email ?? '';

    guestNumber.value =
        user.guestNumber ?? '';

    allergy.value =
        user.allergy ?? '';
}


/**
 * Initialise la gestion du formulaire du compte.
 */
function initAccountForm() {
    const form = document.getElementById(
        'account-form'
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

            const firstNameInput =
                document.getElementById(
                    'account-first-name'
                );

            const lastNameInput =
                document.getElementById(
                    'account-last-name'
                );

            const emailInput =
                document.getElementById(
                    'account-email'
                );

            const guestNumberInput =
                document.getElementById(
                    'account-guest-number'
                );

            const allergyInput =
                document.getElementById(
                    'account-allergy'
                );

            if (
                !firstNameInput
                || !lastNameInput
                || !emailInput
                || !guestNumberInput
                || !allergyInput
            ) {
                return;
            }

            const firstName =
                firstNameInput.value.trim();

            const lastName =
                lastNameInput.value.trim();

            const email =
                emailInput.value.trim();

            const guestNumber =
                Number(guestNumberInput.value);

            const allergy =
                allergyInput.value.trim();

            if (
                !firstName
                || !lastName
                || !email
            ) {
                showError(
                    'Veuillez remplir tous les champs obligatoires.'
                );

                return;
            }

            if (
                !Number.isInteger(guestNumber)
                || guestNumber < 1
                || guestNumber > 50
            ) {
                showError(
                    'Le nombre de personnes doit être compris entre 1 et 50.'
                );

                return;
            }

            const token = getToken();

            if (!token) {
                showError(
                    'Votre session a expiré. Veuillez vous reconnecter.'
                );

                return;
            }

            try {
                const response = await fetch(
                    `${API_URL}/me`,
                    {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${token}`
                        },
                        body: JSON.stringify({
                            firstName,
                            lastName,
                            email,
                            guestNumber,
                            allergy: allergy || null
                        })
                    }
                );

                let data = {};

                try {
                    data = await response.json();
                } catch {
                    data = {};
                }

                if (!response.ok) {
                    throw new Error(
                        data.message
                        ?? 'Impossible de modifier les informations du compte.'
                    );
                }

                const updatedUser =
                    await getCurrentUser();

                if (updatedUser) {
                    displayUser(updatedUser);
                }

                showSuccess(
                    'Vos informations ont été modifiées avec succès.'
                );

            } catch (error) {
                showError(
                    error.message
                    ?? 'Une erreur est survenue lors de la modification du compte.'
                );
            }
        }
    );
}


/**
 * Initialise la page du compte.
 */
export async function init() {
    hideError();
    hideSuccess();

    if (!isAuthenticated()) {
        showError(
            'Vous devez être connecté pour accéder à votre compte.'
        );

        return;
    }

    const user = await getCurrentUser();

    if (!user) {
        showError(
            'Impossible de récupérer les informations de votre compte.'
        );

        return;
    }

    displayUser(user);
    initAccountForm();
}