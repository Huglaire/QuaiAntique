// Élément dans lequel les pages sont affichées
const mainPage = document.getElementById('main-page');

// Pages disponibles dans l'application
const routes = {
    '/': 'pages/home.html',
    '/menus': 'pages/menus.html',
    '/galerie': 'pages/galerie.html',
    '/connexion': 'pages/connexion.html',
    '/inscription': 'pages/inscription.html',
};


// Charge une page dans le contenu principal
async function loadPage(path) {
    const page = routes[path];

    // Affiche une erreur si la route n'existe pas
    if (!page) {
        mainPage.innerHTML = `
            <section>
                <h1>Page introuvable</h1>
                <p>La page demandée n'existe pas.</p>
            </section>
        `;

        return;
    }

    try {
        const response = await fetch(page);

        // Vérifie que le fichier HTML existe
        if (!response.ok) {
            throw new Error(
                `Impossible de charger ${page}`
            );
        }

        mainPage.innerHTML = await response.text();

    } catch (error) {
        console.error(error);

        mainPage.innerHTML = `
            <section>
                <h1>Une erreur est survenue</h1>
                <p>
                    Impossible de charger cette page.
                </p>
            </section>
        `;
    }
}


// Change de page sans recharger le navigateur
function navigate(path) {
    window.history.pushState({}, '', path);

    loadPage(path);
}


// Gère les clics sur les liens internes
document.addEventListener('click', (event) => {
    const link = event.target.closest('a');

    // Ignore les clics qui ne concernent pas un lien
    if (!link) {
        return;
    }

    const url = new URL(link.href);

    // Laisse le navigateur gérer les liens externes
    if (url.origin !== window.location.origin) {
        return;
    }

    event.preventDefault();

    navigate(url.pathname);
});


// Gère les boutons précédent / suivant du navigateur
window.addEventListener('popstate', () => {
    loadPage(window.location.pathname);
});


// Charge la page correspondant à l'URL actuelle
loadPage(window.location.pathname);