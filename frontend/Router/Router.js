import allRoutes from './allRoutes.js';

import {
    getCurrentUser,
    isAuthenticated,
    updateNavigation,
    initLogout
} from '../js/script.js';


// Zone dans laquelle les pages sont affichées
const mainPage = document.getElementById('main-page');


// Met à jour la navigation
async function refreshNavigation() {
    await updateNavigation();
}


// Affiche un message dans la zone principale
function displayMessage(title, message) {
    const section = document.createElement('section');
    section.classList.add('container', 'py-5');

    const heading = document.createElement('h1');
    heading.textContent = title;

    const paragraph = document.createElement('p');
    paragraph.textContent = message;

    section.append(
        heading,
        paragraph
    );

    mainPage.replaceChildren(section);
}


// Vérifie si l'utilisateur possède le rôle nécessaire
async function checkRouteAccess(route) {
    if (!route.roles || route.roles.length === 0) {
        return true;
    }

    if (!isAuthenticated()) {
        return false;
    }

    const user = await getCurrentUser();

    if (!user) {
        return false;
    }

    return route.roles.some(
        (role) => user.roles?.includes(role)
    );
}


// Charge la page correspondant à l'URL
async function loadRoute(path) {

    const route = allRoutes.find(
        (route) => route.path === path
    );

    // Affiche une erreur si la route n'existe pas
    if (!route) {

        displayMessage(
            'Page introuvable',
            'La page demandée n’existe pas.'
        );

        document.title =
            'Page introuvable - Quai Antique';

        await refreshNavigation();

        return;
    }

    // Vérifie les droits d'accès à la route
    const hasAccess = await checkRouteAccess(route);

    if (!hasAccess) {

        window.history.pushState(
            {},
            '',
            '/connexion'
        );

        await loadRoute('/connexion');

        return;
    }

    try {

        // Récupère le contenu HTML de la page
        const response = await fetch(route.view);

        if (!response.ok) {
            throw new Error(
                `Impossible de charger ${route.view}`
            );
        }

        const html = await response.text();

        // Transforme le HTML reçu en document temporaire
        const parser = new DOMParser();

        const documentPage = parser.parseFromString(
            html,
            'text/html'
        );

        // Injecte les éléments de la page dans le SPA
        mainPage.replaceChildren(
            ...Array.from(
                documentPage.body.childNodes
            )
        );

        // Met à jour le titre de l'onglet
        document.title =
            `${route.title} - Quai Antique`;

        // Charge le script associé à la page
        if (route.script) {

            const pageModule = await import(
                route.script
            );

            if (
                typeof pageModule.init === 'function'
            ) {
                await pageModule.init();
            }
        }

        // Met à jour la navigation
        await refreshNavigation();

    } catch (error) {

        console.error(error);

        displayMessage(
            'Erreur',
            'Impossible de charger la page.'
        );

        await refreshNavigation();
    }
}


// Gère la navigation interne
function navigate(path) {

    window.history.pushState(
        {},
        '',
        path
    );

    loadRoute(path);
}


// Intercepte les clics sur les liens
document.addEventListener(
    'click',
    (event) => {

        const link = event.target.closest('a');

        if (!link) {
            return;
        }

        // Laisse la gestion de la déconnexion
        // au système d'authentification
        if (
            link.dataset.authAction === 'logout'
        ) {
            return;
        }

        const url = new URL(
            link.href,
            window.location.origin
        );

        // Laisse les liens externes au navigateur
        if (
            url.origin !== window.location.origin
        ) {
            return;
        }

        event.preventDefault();

        navigate(url.pathname);
    }
);


// Gère les boutons précédent / suivant
window.addEventListener(
    'popstate',
    () => {
        loadRoute(
            window.location.pathname
        );
    }
);


// Initialise la gestion de la déconnexion
initLogout();


// Charge la page au démarrage
loadRoute(
    window.location.pathname
);