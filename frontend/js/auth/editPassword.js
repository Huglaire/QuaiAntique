import {
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
        'password-error'
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
        'password-error'
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
        'password-success'
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
        'password-success'
    );

    if (!success) {
        return;
    }

    success.textContent = '';
    success.classList.add('d-none');
}


/**
 * Initialise le formulaire de changement de mot de passe.
 */
function initPasswordForm() {
    const form = document.getElementById(
        'password-form'
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

            const currentPasswordInput =
                document.getElementById(
                    'current-password'
                );

            const newPasswordInput =
                document.getElementById(
                    'new-password'
                );

            const confirmPasswordInput =
                document.getElementById(
                    'confirm-password'
                );

            if (
                !currentPasswordInput
                || !newPasswordInput
                || !confirmPasswordInput
            ) {
                return;
            }

            const currentPassword =
                currentPasswordInput.value;

            const newPassword =
                newPasswordInput.value;

            const confirmPassword =
                confirmPasswordInput.value;

            // Vérifie que tous les champs sont remplis.
            if (
                !currentPassword
                || !newPassword
                || !confirmPassword
            ) {
                showError(
                    'Veuillez remplir tous les champs.'
                );

                return;
            }

            // Vérifie la longueur du nouveau mot de passe.
            if (newPassword.length < 8) {
                showError(
                    'Le nouveau mot de passe doit contenir au moins 8 caractères.'
                );

                return;
            }

            // Vérifie la confirmation du nouveau mot de passe.
            if (newPassword !== confirmPassword) {
                showError(
                    'Les deux nouveaux mots de passe ne correspondent pas.'
                );

                return;
            }

            // Évite de choisir le même mot de passe.
            if (currentPassword === newPassword) {
                showError(
                    'Le nouveau mot de passe doit être différent de l’ancien.'
                );

                return;
            }

            const token = getToken();

            if (!token || !isAuthenticated()) {
                showError(
                    'Votre session a expiré. Veuillez vous reconnecter.'
                );

                return;
            }

            try {
                const response = await fetch(
                    `${API_URL}/me/password`,
                    {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${token}`
                        },
                        body: JSON.stringify({
                            currentPassword,
                            newPassword
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
                        ?? 'Impossible de modifier le mot de passe.'
                    );
                }

                form.reset();

                showSuccess(
                    'Votre mot de passe a été modifié avec succès.'
                );

            } catch (error) {
                showError(
                    error.message
                    ?? 'Une erreur est survenue lors de la modification du mot de passe.'
                );
            }
        }
    );
}


/**
 * Initialise la page de changement de mot de passe.
 */
export async function init() {
    hideError();
    hideSuccess();

    if (!isAuthenticated()) {
        showError(
            'Vous devez être connecté pour modifier votre mot de passe.'
        );

        return;
    }

    initPasswordForm();
}