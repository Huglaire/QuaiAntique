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
        '/pages/menus.html',
        '/js/menus.js'
    ),

    new Route(
        '/galerie',
        'Galerie',
        '/pages/galerie.html',
        '/js/galerie.js'
    ),

    new Route(
        '/connexion',
        'Connexion',
        '/pages/auth/signin.html',
        '/js/auth/signin.js'
    ),

    new Route(
        '/inscription',
        'Inscription',
        '/pages/auth/signup.html',
        '/js/auth/signup.js'
    ),

    new Route(
        '/reservation',
        'Réservation',
        '/pages/reservation.html'
    )
];

export default allRoutes;