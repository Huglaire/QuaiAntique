import allRoutes from './allRoutes.js';


// Zone dans laquelle les pages sont affichées
const mainPage =
    document.getElementById('main-page');


/**
 * Affiche un titre et un message dans la page principale.
 */
function displayMessage(titleText, messageText) {

    mainPage.replaceChildren();


    const section =
        document.createElement('section');


    const title =
        document.createElement('h1');

    title.textContent = titleText;


    const message =
        document.createElement('p');

    message.textContent = messageText;


    section.append(
        title,
        message
    );


    mainPage.append(section);
}


/**
 * Charge la page correspondant à l'URL.
 */
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

        return;
    }


    try {

        // Récupère le contenu HTML de la page
        const response =
            await fetch(route.view);


        if (!response.ok) {

            throw new Error(
                `Impossible de charger ${route.view}`
            );
        }


        const html =
            await response.text();


        // Transforme le HTML récupéré en document
        const parser =
            new DOMParser();

        const documentPage =
            parser.parseFromString(
                html,
                'text/html'
            );


        // Remplace le contenu actuel de la page
        mainPage.replaceChildren(
            ...Array.from(
                documentPage.body.childNodes
            )
        );


        // Met à jour le titre de l'onglet
        document.title =
            `${route.title} - Quai Antique`;


        // Charge le JavaScript propre à la page
        if (route.script) {

            const pageModule =
                await import(route.script);


            // Lance l'initialisation de la page
            if (
                typeof pageModule.init === 'function'
            ) {

                pageModule.init();
            }
        }

    } catch (error) {

        console.error(error);


        displayMessage(
            'Erreur',
            'Impossible de charger la page.'
        );
    }
}


/**
 * Gère la navigation interne.
 */
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

        const link =
            event.target.closest('a');


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


// Charge la page au démarrage
loadRoute(
    window.location.pathname
);