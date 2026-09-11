import {
    getCurrentUser,
    isAuthenticated
} from '../script.js';


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
 * Affiche les informations du compte.
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

    firstName.textContent =
        user.firstName ?? '';

    lastName.textContent =
        user.lastName ?? '';

    email.textContent =
        user.email ?? '';

    guestNumber.textContent =
        user.guestNumber ?? '';

    allergy.textContent =
        user.allergy || 'Aucune';
}


/**
 * Initialise la page du compte.
 */
export async function init() {
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
}