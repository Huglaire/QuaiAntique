import Route from './Route.js';

// Liste des routes publiques de l'application
const allRoutes = [

    new Route(
        '/',
        'Accueil',
        '/pages/home.html'
    ),

    new Route(
        '/menus',
        'La carte',
        '/pages/menus.html'
    ),

    new Route(
        '/galerie',
        'Galerie',
        '/pages/galerie.html'
    ),

    new Route(
        '/connexion',
        'Connexion',
        '/pages/connexion.html'
    ),

    new Route(
        '/inscription',
        'Inscription',
        '/pages/inscription.html'
    ),

    new Route(
        '/reservation',
        'Réservation',
        '/pages/reservation.html'
    )
];

export default allRoutes;