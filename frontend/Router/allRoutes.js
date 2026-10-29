import Route from './Route.js';

// Liste des routes de l'application
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
        '/compte',
        'Mon compte',
        '/pages/auth/account.html',
        '/js/auth/account.js',
        ['ROLE_USER']
    ),

    new Route(
        '/compte/mot-de-passe',
        'Modifier mon mot de passe',
        '/pages/auth/editPassword.html',
        '/js/auth/editPassword.js',
        ['ROLE_USER']
    ),

    new Route(
        '/reservation',
        'Réserver une table',
        '/pages/reservations/reserver.html',
        '/js/reservations/reserver.js',
        ['ROLE_USER']
    ),

    new Route(
        '/reservations',
        'Mes réservations',
        '/pages/reservations/allResa.html',
        '/js/reservations/allResa.js',
        ['ROLE_USER']
    ),

    new Route(
        '/admin',
        'Administration',
        '/pages/admin/dashboard.html',
        null,
        ['ROLE_ADMIN']
    ),

    new Route(
        '/admin/reservations',
        'Gestion des réservations',
        '/pages/admin/bookings.html',
        '/js/admin/bookings.js',
        ['ROLE_ADMIN']
    ),

    new Route(
        '/admin/restaurant',
        'Informations du restaurant',
        '/pages/admin/restaurant.html',
        '/js/admin/restaurant.js',
        ['ROLE_ADMIN']
    ),

    new Route(
        '/admin/gallery',
        'Gestion de la galerie',
        '/pages/admin/gallery.html',
        '/js/admin/gallery.js',
        ['ROLE_ADMIN']
    ),

    new Route(
        '/admin/categories',
        'Gestion des catégories',
        '/pages/admin/categories.html',
        '/js/admin/categories.js',
        ['ROLE_ADMIN']
    ),

    new Route(
        '/admin/plats',
        'Gestion des plats',
        '/pages/admin/foods.html',
        '/js/admin/foods.js',
        ['ROLE_ADMIN']
    ),

    new Route(
        '/admin/menus',
        'Gestion des menus',
        '/pages/admin/menus.html',
        '/js/admin/menus.js',
        ['ROLE_ADMIN']
    ),

    new Route(
        '/admin/statistics',
        'Statistiques',
        '/pages/admin/statistics.html',
        '/js/admin/statistics.js',
        ['ROLE_ADMIN']
    )
];

export default allRoutes;