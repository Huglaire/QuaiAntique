const API_URL = '/api';


/**
 * Crée un élément HTML avec une classe CSS.
 *
 * @param {string} tagName
 * @param {string} className
 * @returns {HTMLElement}
 */
function createElement(tagName, className = '') {

    const element = document.createElement(tagName);

    if (className) {
        element.classList.add(
            ...className.split(' ')
        );
    }

    return element;
}


/**
 * Crée une carte représentant un menu.
 *
 * @param {Object} menu
 * @returns {HTMLElement}
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

    title.textContent =
        menu.title;

    const price = createElement(
        'p',
        'fw-bold mb-0'
    );

    price.textContent =
        `${menu.price} €`;

    header.append(
        title,
        price
    );

    const description = createElement(
        'p',
        'card-text'
    );

    description.textContent =
        menu.description;

    const compositionTitle = createElement(
        'h3',
        'h5 mt-2'
    );

    compositionTitle.textContent =
        'Composition';

    const foodsList = createElement(
        'ul',
        'mb-0'
    );

    if (Array.isArray(menu.foods)) {

        menu.foods.forEach((food) => {

            const foodItem =
                createElement('li');

            const foodName =
                createElement('span');

            foodName.textContent =
                food.title;

            const foodPrice =
                createElement('span');

            foodPrice.textContent =
                ` — ${food.price} €`;

            foodItem.append(
                foodName,
                foodPrice
            );

            foodsList.append(
                foodItem
            );
        });
    }

    cardBody.append(
        header,
        description,
        compositionTitle,
        foodsList
    );

    card.append(
        cardBody
    );

    column.append(
        card
    );

    return column;
}


/**
 * Affiche les menus dans la page.
 *
 * @param {Array} menus
 * @param {HTMLElement} container
 */
function renderMenus(
    menus,
    container
) {

    container.replaceChildren();

    if (
        !Array.isArray(menus)
        || menus.length === 0
    ) {

        const column = createElement(
            'div',
            'col-12 text-center'
        );

        const message =
            createElement('p');

        message.textContent =
            'Aucun menu n’est disponible pour le moment.';

        column.append(
            message
        );

        container.append(
            column
        );

        return;
    }

    const fragment =
        document.createDocumentFragment();

    menus.forEach((menu) => {

        fragment.append(
            createMenuCard(menu)
        );
    });

    container.append(
        fragment
    );
}


/**
 * Crée une carte représentant un plat.
 *
 * @param {Object} food
 * @returns {HTMLElement}
 */
function createFoodCard(food) {

    const column = createElement(
        'article',
        'col-12 col-md-6 col-lg-4'
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
        'h3',
        'h5 card-title mb-0'
    );

    title.textContent =
        food.title;

    const price = createElement(
        'p',
        'fw-bold mb-0 text-nowrap'
    );

    price.textContent =
        `${food.price} €`;

    header.append(
        title,
        price
    );

    const description =
        createElement(
            'p',
            'card-text mb-0'
        );

    description.textContent =
        food.description;

    cardBody.append(
        header,
        description
    );

    card.append(
        cardBody
    );

    column.append(
        card
    );

    return column;
}


/**
 * Crée une section correspondant à une catégorie de plats.
 *
 * @param {string} categoryTitle
 * @param {Array} foods
 * @returns {HTMLElement}
 */
function createFoodCategory(
    categoryTitle,
    foods
) {

    const section =
        createElement(
            'section',
            'mb-5'
        );

    const heading =
        createElement(
            'h3',
            'text-primary mb-4'
        );

    heading.textContent =
        categoryTitle;

    const grid =
        createElement(
            'div',
            'row g-4'
        );

    foods.forEach((food) => {

        grid.append(
            createFoodCard(food)
        );
    });

    section.append(
        heading,
        grid
    );

    return section;
}


/**
 * Affiche les plats regroupés par catégorie.
 *
 * @param {Array} foods
 * @param {HTMLElement} container
 */
function renderFoods(
    foods,
    container
) {

    container.replaceChildren();

    if (
        !Array.isArray(foods)
        || foods.length === 0
    ) {

        const message =
            createElement('p');

        message.classList.add(
            'text-center'
        );

        message.textContent =
            'Aucun plat n’est disponible pour le moment.';

        container.append(
            message
        );

        return;
    }

    /*
     * Les catégories historiques du restaurant
     * restent affichées en premier lorsqu'elles existent.
     */
    const preferredCategoryOrder = [
        'Entrées',
        'Plats',
        'Desserts'
    ];

    /*
     * Regroupe tous les plats par catégorie.
     *
     * Contrairement à l'ancienne version,
     * aucune catégorie n'est ignorée.
     */
    const foodsByCategory = new Map();

    foods.forEach((food) => {

        const categoryTitle =
            food.category?.title;

        if (!categoryTitle) {
            return;
        }

        if (!foodsByCategory.has(categoryTitle)) {
            foodsByCategory.set(
                categoryTitle,
                []
            );
        }

        foodsByCategory
            .get(categoryTitle)
            .push(food);
    });

    /*
     * Prépare l'ordre final des catégories.
     *
     * Les trois catégories historiques sont prioritaires,
     * puis les nouvelles catégories sont ajoutées
     * dans l'ordre alphabétique.
     */
    const categoryNames = [
        ...foodsByCategory.keys()
    ];

    const orderedCategories = [];

    preferredCategoryOrder.forEach(
        (categoryTitle) => {

            if (
                foodsByCategory.has(
                    categoryTitle
                )
            ) {
                orderedCategories.push(
                    categoryTitle
                );
            }
        }
    );

    const additionalCategories =
        categoryNames
            .filter(
                (categoryTitle) =>
                    !preferredCategoryOrder.includes(
                        categoryTitle
                    )
            )
            .sort(
                (categoryA, categoryB) =>
                    categoryA.localeCompare(
                        categoryB,
                        'fr'
                    )
            );

    orderedCategories.push(
        ...additionalCategories
    );

    const fragment =
        document.createDocumentFragment();

    orderedCategories.forEach(
        (categoryTitle) => {

            const categoryFoods =
                foodsByCategory.get(
                    categoryTitle
                );

            if (
                !categoryFoods
                || categoryFoods.length === 0
            ) {
                return;
            }

            /*
             * Trie les plats par ordre alphabétique
             * à l'intérieur de leur catégorie.
             */
            categoryFoods.sort(
                (foodA, foodB) =>
                    foodA.title.localeCompare(
                        foodB.title,
                        'fr'
                    )
            );

            fragment.append(
                createFoodCategory(
                    categoryTitle,
                    categoryFoods
                )
            );
        }
    );

    container.append(
        fragment
    );
}


/**
 * Affiche un message d'erreur pour les menus.
 *
 * @param {HTMLElement} container
 */
function renderMenuError(container) {

    container.replaceChildren();

    const column = createElement(
        'div',
        'col-12 text-center'
    );

    const message =
        createElement('p');

    message.textContent =
        'Impossible de charger les menus pour le moment.';

    column.append(
        message
    );

    container.append(
        column
    );
}


/**
 * Affiche un message d'erreur pour les plats.
 *
 * @param {HTMLElement} container
 */
function renderFoodError(container) {

    container.replaceChildren();

    const message =
        createElement(
            'p',
            'text-center'
        );

    message.textContent =
        'Impossible de charger les plats pour le moment.';

    container.append(
        message
    );
}


/**
 * Récupère les menus depuis l'API Symfony.
 *
 * @returns {Promise<Array>}
 */
async function fetchMenus() {

    const response =
        await fetch(
            `${API_URL}/menus`
        );

    if (!response.ok) {
        throw new Error(
            'Impossible de récupérer les menus.'
        );
    }

    return await response.json();
}


/**
 * Récupère les plats depuis l'API Symfony.
 *
 * @returns {Promise<Array>}
 */
async function fetchFoods() {

    const response =
        await fetch(
            `${API_URL}/foods`
        );

    if (!response.ok) {
        throw new Error(
            'Impossible de récupérer les plats.'
        );
    }

    return await response.json();
}


/**
 * Charge les menus et les affiche dans la page.
 */
async function loadMenus() {

    const container =
        document.getElementById(
            'menus-container'
        );

    if (!container) {
        return;
    }

    try {

        const menus =
            await fetchMenus();

        renderMenus(
            menus,
            container
        );

    } catch (error) {

        console.error(error);

        renderMenuError(
            container
        );
    }
}


/**
 * Charge les plats et les affiche dans la page.
 */
async function loadFoods() {

    const container =
        document.getElementById(
            'foods-container'
        );

    if (!container) {
        return;
    }

    try {

        const foods =
            await fetchFoods();

        renderFoods(
            foods,
            container
        );

    } catch (error) {

        console.error(error);

        renderFoodError(
            container
        );
    }
}


/**
 * Initialise la page des menus.
 */
export function init() {

    loadMenus();
    loadFoods();
}