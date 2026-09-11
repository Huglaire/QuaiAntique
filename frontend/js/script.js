const API_URL = 'http://127.0.0.1:8000/api';

/**
 * Récupère le token JWT enregistré.
 *
 * @returns {string|null}
 */
export function getToken() {
    return localStorage.getItem('jwt');
}

/**
 * Vérifie si un utilisateur possède un token JWT.
 *
 * @returns {boolean}
 */
export function isAuthenticated() {
    return getToken() !== null;
}

/**
 * Récupère les informations de l'utilisateur connecté.
 *
 * @returns {Promise<Object|null>}
 */
export async function getCurrentUser() {
    const token = getToken();

    if (!token) {
        return null;
    }

    try {
        const response = await fetch(
            `${API_URL}/me`,
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            }
        );

        if (!response.ok) {
            return null;
        }

        return await response.json();

    } catch {
        return null;
    }
}

/**
 * Déconnecte l'utilisateur.
 */
export function logout() {
    localStorage.removeItem('jwt');
}

/**
 * Vérifie si l'utilisateur connecté possède le rôle administrateur.
 *
 * @param {Object|null} user
 * @returns {boolean}
 */
export function isAdmin(user) {
    return user?.roles?.includes('ROLE_ADMIN') ?? false;
}

/**
 * Met à jour le lien de connexion dans la navigation.
 *
 * Le lien existant dans index.html est conservé.
 * Il est simplement adapté selon l'état de connexion.
 */
export async function updateNavigation() {
    const loginLink = document.querySelector(
        'a[href="/connexion"]'
    );

    if (!loginLink) {
        return;
    }

    const user = await getCurrentUser();

    if (user) {
        loginLink.textContent = 'Déconnexion';
        loginLink.setAttribute(
            'data-auth-action',
            'logout'
        );

        return;
    }

    loginLink.textContent = 'Connexion';
    loginLink.removeAttribute('data-auth-action');
}

/**
 * Initialise la gestion de la déconnexion.
 */
export function initLogout() {
    document.addEventListener(
        'click',
        (event) => {
            const logoutLink = event.target.closest(
                '[data-auth-action="logout"]'
            );

            if (!logoutLink) {
                return;
            }

            event.preventDefault();

            logout();

            window.history.pushState(
                {},
                '',
                '/'
            );

            window.dispatchEvent(
                new PopStateEvent('popstate')
            );
        }
    );
}