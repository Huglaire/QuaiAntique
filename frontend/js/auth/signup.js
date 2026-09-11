const API_URL = 'http://127.0.0.1:8000/api';

/**
 * Affiche un message d'erreur.
 */
function showError(message) {
    const error = document.getElementById('signup-error');

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
    const error = document.getElementById('signup-error');

    if (!error) {
        return;
    }

    error.textContent = '';
    error.classList.add('d-none');
}

/**
 * Affiche un message de succès.
 */
function showSuccess(message) {
    const success = document.getElementById('signup-success');

    if (!success) {
        return;
    }

    success.textContent = message;
    success.classList.remove('d-none');
}

/**
 * Initialise le formulaire d'inscription.
 */
export function init() {
    const form = document.getElementById('signup-form');

    if (!form) {
        return;
    }

    if (form.dataset.initialized === 'true') {
        return;
    }

    form.dataset.initialized = 'true';

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        hideError();

        const firstName = document
            .getElementById('firstName')
            .value
            .trim();

        const lastName = document
            .getElementById('lastName')
            .value
            .trim();

        const email = document
            .getElementById('email')
            .value
            .trim();

        const password = document
            .getElementById('password')
            .value;

        const confirmPassword = document
            .getElementById('confirmPassword')
            .value;

        const guestNumber = Number(
            document
                .getElementById('guestNumber')
                .value
        );

        const allergy = document
            .getElementById('allergy')
            .value
            .trim();

        if (
            !firstName
            || !lastName
            || !email
            || !password
            || !confirmPassword
        ) {
            showError(
                'Veuillez remplir tous les champs obligatoires.'
            );

            return;
        }

        if (password !== confirmPassword) {
            showError(
                'Les mots de passe ne correspondent pas.'
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

        try {
            const response = await fetch(
                `${API_URL}/register`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        firstName,
                        lastName,
                        email,
                        password,
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
                    ?? 'Une erreur est survenue lors de la création du compte.'
                );
            }

            showSuccess(
                'Votre compte a été créé avec succès.'
            );

            form.reset();

            window.setTimeout(() => {
                window.history.pushState(
                    {},
                    '',
                    '/connexion'
                );

                window.dispatchEvent(
                    new PopStateEvent('popstate')
                );
            }, 1000);

        } catch (error) {
            showError(
                error.message
                ?? 'Une erreur est survenue lors de la création du compte.'
            );
        }
    });
}