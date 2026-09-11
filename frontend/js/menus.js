const API_URL = 'http://127.0.0.1:8000/api';


/**
 * Crée un élément HTML avec une classe CSS.
 */
function createElement(tagName, className = '') {

    const element = document.createElement(tagName);

    if (className) {
        element.classList.add(...className.split(' '));
    }

    return element;
}


/**
 * Crée une carte représentant un menu.
 */
function createMenuCard(menu) {

    const column = createElement(
        'article',
        'col-12 col-lg-6'
    );

    const card = createElement(
        'div',
        'card h-100'
    );

    const cardBody = createElement(
        'div',
        'card-body d-flex flex-column'
    );

    const header = createElement(
        'div',
        'd-flex justify-content-between gap-3 mb-3'
    );

    const title = createElement(
        'h2',
        'h4 card-title mb-0'
    );

    title.textContent = menu.title;

    const price = createElement(
        'p',
        'fw-bold mb-0'
    );

    price.textContent = `${menu.price} €`;

    header.append(title, price);


    const description = createElement(
        'p',
        'card-text'
    );

    description.textContent = menu.description;


    const compositionTitle = createElement(
        'h3',
        'h5 mt-2'
    );

    compositionTitle.textContent = 'Composition';


    const foodsList = createElement(
        'ul',
        'mb-0'
    );


    if (Array.isArray(menu.foods)) {

        menu.foods.forEach((food) => {

            const foodItem = createElement('li');

            const foodName = createElement('span');

            foodName.textContent = food.title;

            const foodPrice = createElement('span');

            foodPrice.textContent =
                ` — ${food.price} €`;

            foodItem.append(
                foodName,
                foodPrice
            );

            foodsList.append(foodItem);
        });
    }


    cardBody.append(
        header,
        description,
        compositionTitle,
        foodsList
    );

    card.append(cardBody);

    column.append(card);

    return column;
}


/**
 * Affiche les menus dans la page.
 */
function renderMenus(menus, container) {

    container.replaceChildren();


    if (!Array.isArray(menus) || menus.length === 0) {

        const column = createElement(
            'div',
            'col-12 text-center'
        );

        const message = createElement('p');

        message.textContent =
            'Aucun menu n’est disponible pour le moment.';

        column.append(message);

        container.append(column);

        return;
    }


    const fragment =
        document.createDocumentFragment();


    menus.forEach((menu) => {

        fragment.append(
            createMenuCard(menu)
        );
    });


    container.append(fragment);
}


/**
 * Affiche un message d'erreur dans la page.
 */
function renderError(container) {

    container.replaceChildren();


    const column = createElement(
        'div',
        'col-12 text-center'
    );

    const message = createElement('p');

    message.textContent =
        'Impossible de charger les menus pour le moment.';

    column.append(message);

    container.append(column);
}


/**
 * Récupère les menus depuis l'API Symfony.
 */
async function loadMenus() {

    const container =
        document.getElementById('menus-container');


    if (!container) {
        return;
    }


    try {

        const response =
            await fetch(`${API_URL}/menus`);


        if (!response.ok) {

            throw new Error(
                'Impossible de récupérer les menus.'
            );
        }


        const menus =
            await response.json();


        renderMenus(
            menus,
            container
        );

    } catch (error) {

        console.error(error);

        renderError(container);
    }
}


/**
 * Initialise la page des menus.
 */
export function init() {

    loadMenus();
}