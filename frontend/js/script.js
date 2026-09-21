const API_URL = '/api';

/**
 * Retourne le token JWT enregistré.
 *
 * @returns {string|null}
 */
export function getToken() {
    return localStorage.getItem('jwt');
}

/**
 * Vérifie si un utilisateur est authentifié.
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
 * Vérifie si l'utilisateur possède le rôle administrateur.
 *
 * @param {Object|null} user
 * @returns {boolean}
 */
export function isAdmin(user) {
    return user?.roles?.includes('ROLE_ADMIN') ?? false;
}

/**
 * Récupère les informations du restaurant.
 *
 * @returns {Promise<Object|null>}
 */
export async function getRestaurantInformation() {
    try {
        const response = await fetch(
            `${API_URL}/restaurant`,
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        if (!response.ok) {
            return null;
        }

        return await response.json();

    } catch (error) {
        console.error(error);

        return null;
    }
}

/**
 * Met à jour les horaires affichés dans le footer.
 *
 * @returns {Promise<void>}
 */
export async function updateFooterSchedule() {
    const footer = document.querySelector('footer');

    if (!footer) {
        return;
    }

    const headings = footer.querySelectorAll('h3');

    let scheduleHeading = null;

    for (const heading of headings) {
        if (
            heading.textContent.trim() ===
            'Nos horaires'
        ) {
            scheduleHeading = heading;
            break;
        }
    }

    if (!scheduleHeading) {
        return;
    }

    const scheduleContainer =
        scheduleHeading.parentElement;

    if (!scheduleContainer) {
        return;
    }

    const paragraphs =
        scheduleContainer.querySelectorAll('p');

    if (paragraphs.length < 3) {
        return;
    }

    const restaurant =
        await getRestaurantInformation();

    if (!restaurant) {
        return;
    }

    const lunchOpening =
        restaurant.lunchOpeningTime;

    const lunchClosing =
        restaurant.lunchClosingTime;

    const dinnerOpening =
        restaurant.dinnerOpeningTime;

    const dinnerClosing =
        restaurant.dinnerClosingTime;

    if (lunchOpening && lunchClosing) {
        paragraphs[1].textContent =
            `${lunchOpening} - ${lunchClosing}`;
    }

    if (dinnerOpening && dinnerClosing) {
        paragraphs[2].textContent =
            `${dinnerOpening} - ${dinnerClosing}`;
    }
}

/**
 * Met à jour la navigation en fonction
 * de l'utilisateur connecté.
 *
 * @returns {Promise<void>}
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
    } else {
        loginLink.textContent = 'Connexion';

        loginLink.removeAttribute(
            'data-auth-action'
        );
    }

    updateAccountLink(user);
    updateAdministrationLink(user);

    await updateFooterSchedule();
}

/**
 * Ajoute ou supprime le lien vers le compte
 * dans la navigation selon l'état de connexion.
 *
 * @param {Object|null} user
 */
function updateAccountLink(user) {
    const navbar =
        document.querySelector('#navbarNav .navbar-nav');

    if (!navbar) {
        return;
    }

    const existingLink =
        navbar.querySelector(
            'a[href="/compte"]'
        );

    if (user) {
        if (existingLink) {
            return;
        }

        const listItem =
            document.createElement('li');

        listItem.classList.add(
            'nav-item'
        );

        const accountLink =
            document.createElement('a');

        accountLink.classList.add(
            'nav-link'
        );

        accountLink.href =
            '/compte';

        accountLink.textContent =
            'Mon compte';

        listItem.append(
            accountLink
        );

        navbar.append(listItem);

        return;
    }

    if (existingLink) {
        const listItem =
            existingLink.closest('li');

        if (listItem) {
            listItem.remove();
        }
    }
}

/**
 * Ajoute ou supprime le lien d'administration
 * dans la navigation selon le rôle de l'utilisateur.
 *
 * @param {Object|null} user
 */
function updateAdministrationLink(user) {
    const navbar =
        document.querySelector('#navbarNav .navbar-nav');

    if (!navbar) {
        return;
    }

    const existingLink =
        navbar.querySelector(
            'a[href="/admin"]'
        );

    if (isAdmin(user)) {
        if (existingLink) {
            return;
        }

        const listItem =
            document.createElement('li');

        listItem.classList.add(
            'nav-item'
        );

        const administrationLink =
            document.createElement('a');

        administrationLink.classList.add(
            'nav-link'
        );

        administrationLink.href =
            '/admin';

        administrationLink.textContent =
            'Administration';

        listItem.append(
            administrationLink
        );

        navbar.append(listItem);

        return;
    }

    if (existingLink) {
        const listItem =
            existingLink.closest('li');

        if (listItem) {
            listItem.remove();
        }
    }
}

/**
 * Initialise la gestion de la déconnexion.
 */
export function initLogout() {
    document.addEventListener(
        'click',
        (event) => {
            const logoutLink =
                event.target.closest(
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