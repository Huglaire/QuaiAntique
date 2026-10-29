const API_URL = 'http://127.0.0.1:8000/api';

/**
 * Affiche un message d'erreur.
 */
function showError(message) {
    const error = document.getElementById('signin-error');

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
    const error = document.getElementById('signin-error');

    if (!error) {
        return;
    }

    error.textContent = '';
    error.classList.add('d-none');
}

/**
 * Initialise le formulaire de connexion.
 */
export function init() {
    const form = document.getElementById('signin-form');

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

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        if (!emailInput || !passwordInput) {
            return;
        }

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        if (!email || !password) {
            showError(
                'Veuillez renseigner votre adresse e-mail et votre mot de passe.'
            );

            return;
        }

        try {
            const response = await fetch(
                `${API_URL}/login_check`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email,
                        password
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
                    ?? 'Adresse e-mail ou mot de passe incorrect.'
                );
            }

            if (!data.token) {
                throw new Error(
                    'Le serveur n\'a pas retourné de token d\'authentification.'
                );
            }

            localStorage.setItem(
                'jwt',
                data.token
            );

            window.history.pushState(
                {},
                '',
                '/'
            );

            window.dispatchEvent(
                new PopStateEvent('popstate')
            );

        } catch (error) {
            showError(
                error.message
                ?? 'Une erreur est survenue lors de la connexion.'
            );
        }
    });
}