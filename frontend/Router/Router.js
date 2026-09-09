import allRoutes from './allRoutes.js';

// Zone dans laquelle les pages sont affichées
const mainPage = document.getElementById('main-page');


// Charge la page correspondant à l'URL
async function loadRoute(path) {

    const route = allRoutes.find(
        (route) => route.path === path
    );

    // Affiche une erreur si la route n'existe pas
    if (!route) {

        mainPage.innerHTML = `
            <section>
                <h1>Page introuvable</h1>
                <p>
                    La page demandée n'existe pas.
                </p>
            </section>
        `;

        document.title = 'Page introuvable - Quai Antique';

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

        // Injecte la page dans le contenu principal
        mainPage.innerHTML = html;

        // Met à jour le titre de l'onglet
        document.title = `${route.title} - Quai Antique`;

    } catch (error) {

        console.error(error);

        mainPage.innerHTML = `
            <section>
                <h1>Erreur</h1>
                <p>
                    Impossible de charger la page.
                </p>
            </section>
        `;
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
document.addEventListener('click', (event) => {

    const link = event.target.closest('a');

    if (!link) {
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
});


// Gère les boutons précédent / suivant
window.addEventListener('popstate', () => {

    loadRoute(window.location.pathname);
});


// Charge la page au démarrage
loadRoute(window.location.pathname);