const API_URL = 'http://127.0.0.1:8000/api';

/**
 * Récupère le token JWT enregistré.
 *
 * @returns {string|null}
 */
function getToken() {
    return localStorage.getItem('jwt');
}

/**
 * Affiche un message général dans la page.
 *
 * @param {string} message
 * @param {string} type
 */
function displayMessage(message, type = 'info') {
    const messageContainer =
        document.getElementById('menus-message');

    if (!messageContainer) {
        return;
    }

    messageContainer.textContent = message;
    messageContainer.className =
        `alert alert-${type}`;
}

/**
 * Masque le message général.
 */
function hideMessage() {
    const messageContainer =
        document.getElementById('menus-message');

    if (!messageContainer) {
        return;
    }

    messageContainer.textContent = '';
    messageContainer.className =
        'alert d-none';
}

/**
 * Affiche un message sous le formulaire d'ajout.
 *
 * @param {string} message
 * @param {string} type
 */
function displayAddMessage(message, type = 'danger') {
    const messageContainer =
        document.getElementById('menu-add-message');

    if (!messageContainer) {
        return;
    }

    messageContainer.textContent = message;
    messageContainer.className =
        `mt-3 text-${type}`;
}

/**
 * Récupère les plats disponibles.
 *
 * @returns {Promise<Array>}
 */
async function loadFoods() {
    const token = getToken();

    if (!token) {
        throw new Error(
            'Vous devez être connecté pour accéder aux plats.'
        );
    }

    const response = await fetch(
        `${API_URL}/admin/foods`,
        {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization':
                    `Bearer ${token}`
            }
        }
    );

    if (response.status === 401) {
        throw new Error(
            'Votre session a expiré. Veuillez vous reconnecter.'
        );
    }

    if (response.status === 403) {
        throw new Error(
            'Accès réservé aux administrateurs.'
        );
    }

    if (!response.ok) {
        throw new Error(
            'Impossible de récupérer les plats.'
        );
    }

    const foods =
        await response.json();

    if (!Array.isArray(foods)) {
        throw new Error(
            'Les données des plats sont invalides.'
        );
    }

    return foods;
}

/**
 * Remplit une liste déroulante avec les plats.
 *
 * @param {Array} foods
 * @param {Array} selectedUuids
 */
function renderFoodOptions(
    foods,
    selectedUuids = []
) {
    const select =
        document.getElementById('menu-foods');

    if (!select) {
        return;
    }

    select.replaceChildren();

    foods.forEach((food) => {
        const option =
            document.createElement('option');

        option.value = food.uuid;

        option.textContent =
            `${food.title} - ${food.price} €`;

        if (selectedUuids.includes(food.uuid)) {
            option.selected = true;
        }

        select.append(option);
    });
}

/**
 * Récupère les UUID des plats sélectionnés.
 *
 * @param {HTMLSelectElement} select
 * @returns {Array<string>}
 */
function getSelectedFoodUuids(select) {
    return Array.from(
        select.selectedOptions
    ).map(
        (option) => option.value
    );
}

/**
 * Crée un champ de formulaire.
 *
 * @param {string} labelText
 * @param {string} id
 * @param {string} type
 * @param {string} value
 * @returns {HTMLElement}
 */
function createEditField(
    labelText,
    id,
    type,
    value
) {
    const wrapper =
        document.createElement('div');

    wrapper.classList.add('mb-3');

    const label =
        document.createElement('label');

    label.classList.add('form-label');
    label.setAttribute('for', id);
    label.textContent = labelText;

    const input =
        type === 'textarea'
            ? document.createElement('textarea')
            : document.createElement('input');

    input.id = id;
    input.classList.add('form-control');
    input.value = value ?? '';

    if (type === 'textarea') {
        input.rows = 4;
    } else {
        input.type = type;
    }

    wrapper.append(
        label,
        input
    );

    return wrapper;
}

/**
 * Crée le champ de sélection des plats.
 *
 * @param {string} id
 * @param {Array} foods
 * @param {Array<string>} selectedUuids
 * @returns {HTMLElement}
 */
function createFoodField(
    id,
    foods,
    selectedUuids
) {
    const wrapper =
        document.createElement('div');

    wrapper.classList.add('mb-3');

    const label =
        document.createElement('label');

    label.classList.add('form-label');
    label.setAttribute('for', id);
    label.textContent = 'Plats du menu';

    const select =
        document.createElement('select');

    select.id = id;
    select.classList.add('form-select');
    select.multiple = true;
    select.size = 6;

    foods.forEach((food) => {
        const option =
            document.createElement('option');

        option.value = food.uuid;

        option.textContent =
            `${food.title} - ${food.price} €`;

        if (selectedUuids.includes(food.uuid)) {
            option.selected = true;
        }

        select.append(option);
    });

    const help =
        document.createElement('div');

    help.classList.add('form-text');

    help.textContent =
        'Maintenez Ctrl ou Cmd pour sélectionner plusieurs plats.';

    wrapper.append(
        label,
        select,
        help
    );

    return wrapper;
}

/**
 * Crée le formulaire de modification d'un menu.
 *
 * @param {Object} menu
 * @param {Array} foods
 * @param {HTMLElement} cardBody
 */
function createEditForm(
    menu,
    foods,
    cardBody
) {
    const existingForm =
        cardBody.querySelector('form');

    if (existingForm) {
        existingForm.remove();

        return;
    }

    const form =
        document.createElement('form');

    form.classList.add(
        'mt-4',
        'border-top',
        'pt-4'
    );

    const heading =
        document.createElement('h4');

    heading.classList.add(
        'h6',
        'mb-3'
    );

    heading.textContent =
        'Modifier le menu';

    const selectedUuids =
        Array.isArray(menu.foods)
            ? menu.foods.map(
                (food) => food.uuid
            )
            : [];

    const titleField =
        createEditField(
            'Nom du menu',
            `edit-menu-title-${menu.uuid}`,
            'text',
            menu.title
        );

    const titleInput =
        titleField.querySelector('input');

    if (titleInput) {
        titleInput.maxLength = 150;
    }

    const descriptionField =
        createEditField(
            'Description',
            `edit-menu-description-${menu.uuid}`,
            'textarea',
            menu.description
        );

    const priceField =
        createEditField(
            'Prix',
            `edit-menu-price-${menu.uuid}`,
            'number',
            menu.price
        );

    const priceInput =
        priceField.querySelector('input');

    if (priceInput) {
        priceInput.min = '0.01';
        priceInput.step = '0.01';
    }

    const foodField =
        createFoodField(
            `edit-menu-foods-${menu.uuid}`,
            foods,
            selectedUuids
        );

    const actionsContainer =
        document.createElement('div');

    actionsContainer.classList.add(
        'd-flex',
        'gap-2',
        'flex-wrap'
    );

    const saveButton =
        document.createElement('button');

    saveButton.type = 'submit';

    saveButton.classList.add(
        'btn',
        'btn-primary'
    );

    saveButton.textContent =
        'Enregistrer';

    const cancelButton =
        document.createElement('button');

    cancelButton.type = 'button';

    cancelButton.classList.add(
        'btn',
        'btn-secondary'
    );

    cancelButton.textContent =
        'Annuler';

    actionsContainer.append(
        saveButton,
        cancelButton
    );

    form.append(
        heading,
        titleField,
        descriptionField,
        priceField,
        foodField,
        actionsContainer
    );

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const titleInput =
                titleField.querySelector('input');

            const descriptionInput =
                descriptionField.querySelector('textarea');

            const foodSelect =
                foodField.querySelector('select');

            if (
                !titleInput
                || !descriptionInput
                || !priceInput
                || !foodSelect
            ) {
                return;
            }

            const foodUuids =
                getSelectedFoodUuids(foodSelect);

            await updateMenu(
                menu.uuid,
                titleInput.value.trim(),
                descriptionInput.value.trim(),
                priceInput.value.trim(),
                foodUuids
            );
        }
    );

    cancelButton.addEventListener(
        'click',
        () => {
            form.remove();
        }
    );

    cardBody.append(form);
}

/**
 * Crée la carte d'un menu.
 *
 * @param {Object} menu
 * @param {Array} foods
 * @returns {HTMLElement}
 */
function createMenuCard(menu, foods) {
    const column =
        document.createElement('div');

    column.classList.add(
        'col-12',
        'col-lg-6'
    );

    const card =
        document.createElement('article');

    card.classList.add(
        'card',
        'h-100',
        'shadow-sm'
    );

    const body =
        document.createElement('div');

    body.classList.add('card-body');

    const title =
        document.createElement('h3');

    title.classList.add(
        'card-title',
        'h5'
    );

    title.textContent =
        menu.title || 'Sans titre';

    const description =
        document.createElement('p');

    description.classList.add(
        'card-text',
        'mb-3'
    );

    description.textContent =
        menu.description
        || 'Aucune description.';

    const priceParagraph =
        document.createElement('p');

    priceParagraph.classList.add('mb-3');

    const priceLabel =
        document.createElement('strong');

    priceLabel.textContent =
        'Prix : ';

    const priceText =
        document.createTextNode(
            `${menu.price} €`
        );

    priceParagraph.append(
        priceLabel,
        priceText
    );

    const foodsHeading =
        document.createElement('h4');

    foodsHeading.classList.add(
        'h6',
        'mb-2'
    );

    foodsHeading.textContent =
        'Plats du menu';

    const foodsList =
        document.createElement('ul');

    foodsList.classList.add(
        'mb-4'
    );

    if (
        !Array.isArray(menu.foods)
        || menu.foods.length === 0
    ) {
        const emptyItem =
            document.createElement('li');

        emptyItem.textContent =
            'Aucun plat associé.';

        foodsList.append(emptyItem);
    } else {
        menu.foods.forEach((food) => {
            const item =
                document.createElement('li');

            item.textContent =
                `${food.title} - ${food.price} €`;

            foodsList.append(item);
        });
    }

    const actionsContainer =
        document.createElement('div');

    actionsContainer.classList.add(
        'd-flex',
        'gap-2',
        'flex-wrap'
    );

    const editButton =
        document.createElement('button');

    editButton.type = 'button';

    editButton.classList.add(
        'btn',
        'btn-outline-primary'
    );

    editButton.textContent =
        'Modifier';

    editButton.addEventListener(
        'click',
        () => {
            createEditForm(
                menu,
                foods,
                body
            );
        }
    );

    const deleteButton =
        document.createElement('button');

    deleteButton.type = 'button';

    deleteButton.classList.add(
        'btn',
        'btn-outline-danger'
    );

    deleteButton.textContent =
        'Supprimer';

    deleteButton.addEventListener(
        'click',
        () => handleDeleteMenu(menu)
    );

    actionsContainer.append(
        editButton,
        deleteButton
    );

    body.append(
        title,
        description,
        priceParagraph,
        foodsHeading,
        foodsList,
        actionsContainer
    );

    card.append(body);
    column.append(card);

    return column;
}

/**
 * Affiche les menus.
 *
 * @param {Array} menus
 * @param {Array} foods
 */
function renderMenus(menus, foods) {
    const container =
        document.getElementById('menus-container');

    if (!container) {
        return;
    }

    container.replaceChildren();

    if (
        !Array.isArray(menus)
        || menus.length === 0
    ) {
        displayMessage(
            'Aucun menu n\'a encore été créé.',
            'info'
        );

        return;
    }

    hideMessage();

    const fragment =
        document.createDocumentFragment();

    menus.forEach((menu) => {
        fragment.append(
            createMenuCard(
                menu,
                foods
            )
        );
    });

    container.append(fragment);
}

/**
 * Récupère les menus administrateur.
 *
 * @param {Array} foods
 * @returns {Promise<void>}
 */
async function loadMenus(foods) {
    const token = getToken();

    if (!token) {
        displayMessage(
            'Vous devez être connecté pour accéder aux menus.',
            'danger'
        );

        return;
    }

    const container =
        document.getElementById('menus-container');

    if (container) {
        container.replaceChildren();
    }

    hideMessage();

    try {
        const response = await fetch(
            `${API_URL}/admin/menus`,
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization':
                        `Bearer ${token}`
                }
            }
        );

        if (response.status === 401) {
            displayMessage(
                'Votre session a expiré. Veuillez vous reconnecter.',
                'danger'
            );

            return;
        }

        if (response.status === 403) {
            displayMessage(
                'Accès réservé aux administrateurs.',
                'danger'
            );

            return;
        }

        if (!response.ok) {
            throw new Error(
                'Impossible de récupérer les menus.'
            );
        }

        const menus =
            await response.json();

        renderMenus(
            menus,
            foods
        );

    } catch (error) {
        console.error(error);

        displayMessage(
            'Une erreur est survenue lors du chargement des menus.',
            'danger'
        );
    }
}

/**
 * Ajoute un menu.
 *
 * @param {SubmitEvent} event
 * @param {Array} foods
 * @returns {Promise<void>}
 */
async function handleAddMenu(
    event,
    foods
) {
    event.preventDefault();

    const form =
        event.currentTarget;

    const titleInput =
        document.getElementById('menu-title');

    const descriptionInput =
        document.getElementById('menu-description');

    const priceInput =
        document.getElementById('menu-price');

    const foodsSelect =
        document.getElementById('menu-foods');

    if (
        !titleInput
        || !descriptionInput
        || !priceInput
        || !foodsSelect
    ) {
        return;
    }

    displayAddMessage('');

    const title =
        titleInput.value.trim();

    const description =
        descriptionInput.value.trim();

    const price =
        priceInput.value.trim();

    const foodUuids =
        getSelectedFoodUuids(foodsSelect);

    if (!title) {
        displayAddMessage(
            'Le nom du menu est obligatoire.'
        );

        return;
    }

    if (!description) {
        displayAddMessage(
            'La description est obligatoire.'
        );

        return;
    }

    if (!price) {
        displayAddMessage(
            'Le prix est obligatoire.'
        );

        return;
    }

    if (foodUuids.length === 0) {
        displayAddMessage(
            'Veuillez sélectionner au moins un plat.'
        );

        return;
    }

    const validFoodUuids =
        new Set(
            foods.map(
                (food) => food.uuid
            )
        );

    const hasInvalidFood =
        foodUuids.some(
            (uuid) => !validFoodUuids.has(uuid)
        );

    if (hasInvalidFood) {
        displayAddMessage(
            'Un ou plusieurs plats sélectionnés sont invalides.'
        );

        return;
    }

    const token = getToken();

    if (!token) {
        displayAddMessage(
            'Vous devez être connecté pour ajouter un menu.'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/menus`,
            {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type':
                        'application/json',
                    'Authorization':
                        `Bearer ${token}`
                },
                body: JSON.stringify({
                    title,
                    description,
                    price,
                    foodUuids
                })
            }
        );

        let data = null;

        try {
            data = await response.json();
        } catch {
            // La réponse ne contient pas de JSON exploitable.
        }

        if (response.status === 401) {
            displayAddMessage(
                'Votre session a expiré. Veuillez vous reconnecter.'
            );

            return;
        }

        if (response.status === 403) {
            displayAddMessage(
                'Accès réservé aux administrateurs.'
            );

            return;
        }

        if (response.status === 404) {
            displayAddMessage(
                data?.message
                || 'Un plat ou le restaurant est introuvable.'
            );

            return;
        }

        if (!response.ok) {
            displayAddMessage(
                data?.message
                || 'Impossible d\'ajouter le menu.'
            );

            return;
        }

        form.reset();

        displayAddMessage(
            'Menu ajouté avec succès.',
            'success'
        );

        await loadMenus(foods);

    } catch (error) {
        console.error(error);

        displayAddMessage(
            'Une erreur est survenue lors de l\'ajout du menu.'
        );
    }
}

/**
 * Modifie un menu.
 *
 * @param {string} uuid
 * @param {string} title
 * @param {string} description
 * @param {string} price
 * @param {Array<string>} foodUuids
 * @returns {Promise<void>}
 */
async function updateMenu(
    uuid,
    title,
    description,
    price,
    foodUuids
) {
    if (!title) {
        window.alert(
            'Le nom du menu est obligatoire.'
        );

        return;
    }

    if (!description) {
        window.alert(
            'La description est obligatoire.'
        );

        return;
    }

    if (!price) {
        window.alert(
            'Le prix est obligatoire.'
        );

        return;
    }

    if (foodUuids.length === 0) {
        window.alert(
            'Le menu doit contenir au moins un plat.'
        );

        return;
    }

    const token = getToken();

    if (!token) {
        window.alert(
            'Vous devez être connecté pour modifier un menu.'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/menus/${uuid}`,
            {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type':
                        'application/json',
                    'Authorization':
                        `Bearer ${token}`
                },
                body: JSON.stringify({
                    title,
                    description,
                    price,
                    foodUuids
                })
            }
        );

        let data = null;

        try {
            data = await response.json();
        } catch {
            // La réponse ne contient pas de JSON exploitable.
        }

        if (response.status === 400) {
            window.alert(
                data?.message
                || 'Les données du menu sont invalides.'
            );

            return;
        }

        if (response.status === 401) {
            window.alert(
                'Votre session a expiré. Veuillez vous reconnecter.'
            );

            return;
        }

        if (response.status === 403) {
            window.alert(
                'Accès réservé aux administrateurs.'
            );

            return;
        }

        if (response.status === 404) {
            window.alert(
                data?.message
                || 'Menu ou plat introuvable.'
            );

            return;
        }

        if (!response.ok) {
            window.alert(
                data?.message
                || 'Impossible de modifier le menu.'
            );

            return;
        }

        displayMessage(
            'Menu modifié avec succès.',
            'success'
        );

        await loadMenus(foodsCache);

    } catch (error) {
        console.error(error);

        window.alert(
            'Une erreur est survenue lors de la modification du menu.'
        );
    }
}

/**
 * Supprime un menu.
 *
 * @param {Object} menu
 * @returns {Promise<void>}
 */
async function handleDeleteMenu(menu) {
    const confirmed =
        window.confirm(
            `Voulez-vous vraiment supprimer le menu "${menu.title}" ?`
        );

    if (!confirmed) {
        return;
    }

    const token = getToken();

    if (!token) {
        window.alert(
            'Vous devez être connecté pour supprimer un menu.'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/menus/${menu.uuid}`,
            {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Authorization':
                        `Bearer ${token}`
                }
            }
        );

        if (response.status === 400) {
            window.alert(
                'UUID du menu invalide.'
            );

            return;
        }

        if (response.status === 401) {
            window.alert(
                'Votre session a expiré. Veuillez vous reconnecter.'
            );

            return;
        }

        if (response.status === 403) {
            window.alert(
                'Accès réservé aux administrateurs.'
            );

            return;
        }

        if (response.status === 404) {
            window.alert(
                'Menu introuvable.'
            );

            return;
        }

        if (!response.ok) {
            let errorMessage =
                'Impossible de supprimer le menu.';

            try {
                const data =
                    await response.json();

                if (data.message) {
                    errorMessage =
                        data.message;
                }
            } catch {
                // La réponse ne contient pas de JSON exploitable.
            }

            window.alert(errorMessage);

            return;
        }

        displayMessage(
            'Menu supprimé avec succès.',
            'success'
        );

        await loadMenus(foodsCache);

    } catch (error) {
        console.error(error);

        window.alert(
            'Une erreur est survenue lors de la suppression du menu.'
        );
    }
}

/**
 * Stocke les plats actuellement disponibles.
 *
 * Cette variable permet aux actions de modification et de suppression
 * de réutiliser la liste déjà chargée.
 *
 * @type {Array}
 */
let foodsCache = [];

/**
 * Initialise la page de gestion des menus.
 *
 * @returns {Promise<void>}
 */
export async function init() {
    const addForm =
        document.getElementById('menu-add-form');

    const titleInput =
        document.getElementById('menu-title');

    const descriptionInput =
        document.getElementById('menu-description');

    const priceInput =
        document.getElementById('menu-price');

    const foodsSelect =
        document.getElementById('menu-foods');

    const menusContainer =
        document.getElementById('menus-container');

    if (
        !addForm
        || !titleInput
        || !descriptionInput
        || !priceInput
        || !foodsSelect
        || !menusContainer
    ) {
        return;
    }

    try {
        foodsCache =
            await loadFoods();

        renderFoodOptions(
            foodsCache
        );

        addForm.addEventListener(
            'submit',
            (event) => handleAddMenu(
                event,
                foodsCache
            )
        );

        await loadMenus(
            foodsCache
        );

    } catch (error) {
        console.error(error);

        displayMessage(
            error.message
            || 'Une erreur est survenue lors du chargement de la page.',
            'danger'
        );
    }
}