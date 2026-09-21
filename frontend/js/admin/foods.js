const API_URL = '/api';

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
        document.getElementById('foods-message');

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
        document.getElementById('foods-message');

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
        document.getElementById('food-add-message');

    if (!messageContainer) {
        return;
    }

    messageContainer.textContent = message;
    messageContainer.className =
        `mt-3 text-${type}`;
}

/**
 * Récupère les catégories disponibles.
 *
 * @returns {Promise<Array>}
 */
async function loadCategories() {
    const token = getToken();

    if (!token) {
        throw new Error(
            'Vous devez être connecté pour accéder aux catégories.'
        );
    }

    const response = await fetch(
        `${API_URL}/admin/categories`,
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
            'Impossible de récupérer les catégories.'
        );
    }

    const categories =
        await response.json();

    if (!Array.isArray(categories)) {
        throw new Error(
            'Les données des catégories sont invalides.'
        );
    }

    return categories;
}

/**
 * Remplit la liste déroulante des catégories.
 *
 * @param {Array} categories
 */
function renderCategoryOptions(categories) {
    const select =
        document.getElementById('food-category');

    if (!select) {
        return;
    }

    select.replaceChildren();

    const defaultOption =
        document.createElement('option');

    defaultOption.value = '';
    defaultOption.textContent =
        'Sélectionnez une catégorie';

    select.append(defaultOption);

    categories.forEach((category) => {
        const option =
            document.createElement('option');

        option.value = category.uuid;
        option.textContent = category.title;

        select.append(option);
    });
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
 * Crée un champ select pour la catégorie.
 *
 * @param {string} id
 * @param {Array} categories
 * @param {string} selectedUuid
 * @returns {HTMLElement}
 */
function createCategoryField(
    id,
    categories,
    selectedUuid
) {
    const wrapper =
        document.createElement('div');

    wrapper.classList.add('mb-3');

    const label =
        document.createElement('label');

    label.classList.add('form-label');
    label.setAttribute('for', id);
    label.textContent = 'Catégorie';

    const select =
        document.createElement('select');

    select.id = id;
    select.classList.add('form-select');

    categories.forEach((category) => {
        const option =
            document.createElement('option');

        option.value = category.uuid;
        option.textContent = category.title;

        if (category.uuid === selectedUuid) {
            option.selected = true;
        }

        select.append(option);
    });

    wrapper.append(
        label,
        select
    );

    return wrapper;
}

/**
 * Crée le formulaire de modification d'un plat.
 *
 * @param {Object} food
 * @param {Array} categories
 * @param {HTMLElement} cardBody
 */
function createEditForm(
    food,
    categories,
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
        'Modifier le plat';

    const titleField =
        createEditField(
            'Nom du plat',
            `edit-food-title-${food.uuid}`,
            'text',
            food.title
        );

    const descriptionField =
        createEditField(
            'Description',
            `edit-food-description-${food.uuid}`,
            'textarea',
            food.description
        );

    const priceField =
        createEditField(
            'Prix',
            `edit-food-price-${food.uuid}`,
            'number',
            food.price
        );

    const priceInput =
        priceField.querySelector('input');

    if (priceInput) {
        priceInput.min = '0.01';
        priceInput.step = '0.01';
    }

    const categoryField =
        createCategoryField(
            `edit-food-category-${food.uuid}`,
            categories,
            food.category?.uuid
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
        categoryField,
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

            const categorySelect =
                categoryField.querySelector('select');

            if (
                !titleInput
                || !descriptionInput
                || !priceInput
                || !categorySelect
            ) {
                return;
            }

            await updateFood(
                food.uuid,
                titleInput.value.trim(),
                descriptionInput.value.trim(),
                priceInput.value,
                categorySelect.value
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
 * Crée la carte d'un plat.
 *
 * @param {Object} food
 * @param {Array} categories
 * @returns {HTMLElement}
 */
function createFoodCard(food, categories) {
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
        food.title || 'Sans titre';

    const description =
        document.createElement('p');

    description.classList.add(
        'card-text',
        'mb-3'
    );

    description.textContent =
        food.description || 'Aucune description.';

    const information =
        document.createElement('div');

    information.classList.add(
        'mb-4'
    );

    const price =
        document.createElement('p');

    price.classList.add('mb-2');

    const priceLabel =
        document.createElement('strong');

    priceLabel.textContent =
        'Prix : ';

    const priceText =
        document.createTextNode(
            `${food.price} €`
        );

    price.append(
        priceLabel,
        priceText
    );

    const category =
        document.createElement('p');

    category.classList.add('mb-0');

    const categoryLabel =
        document.createElement('strong');

    categoryLabel.textContent =
        'Catégorie : ';

    const categoryText =
        document.createTextNode(
            food.category?.title || 'Aucune'
        );

    category.append(
        categoryLabel,
        categoryText
    );

    information.append(
        price,
        category
    );

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
                food,
                categories,
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
        () => handleDeleteFood(food)
    );

    actionsContainer.append(
        editButton,
        deleteButton
    );

    body.append(
        title,
        description,
        information,
        actionsContainer
    );

    card.append(body);
    column.append(card);

    return column;
}

/**
 * Affiche les plats dans la page.
 *
 * @param {Array} foods
 * @param {Array} categories
 */
function renderFoods(foods, categories) {
    const container =
        document.getElementById('foods-container');

    if (!container) {
        return;
    }

    container.replaceChildren();

    if (
        !Array.isArray(foods)
        || foods.length === 0
    ) {
        displayMessage(
            'Aucun plat n\'a encore été créé.',
            'info'
        );

        return;
    }

    hideMessage();

    const fragment =
        document.createDocumentFragment();

    foods.forEach((food) => {
        fragment.append(
            createFoodCard(
                food,
                categories
            )
        );
    });

    container.append(fragment);
}

/**
 * Récupère les plats administrateur.
 *
 * @param {Array} categories
 * @returns {Promise<void>}
 */
async function loadFoods(categories) {
    const token = getToken();

    if (!token) {
        displayMessage(
            'Vous devez être connecté pour accéder aux plats.',
            'danger'
        );

        return;
    }

    const container =
        document.getElementById('foods-container');

    if (container) {
        container.replaceChildren();
    }

    hideMessage();

    try {
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
                'Impossible de récupérer les plats.'
            );
        }

        const foods =
            await response.json();

        renderFoods(
            foods,
            categories
        );

    } catch (error) {
        console.error(error);

        displayMessage(
            'Une erreur est survenue lors du chargement des plats.',
            'danger'
        );
    }
}

/**
 * Ajoute un plat.
 *
 * @param {SubmitEvent} event
 * @param {Array} categories
 * @returns {Promise<void>}
 */
async function handleAddFood(
    event,
    categories
) {
    event.preventDefault();

    const form =
        event.currentTarget;

    const titleInput =
        document.getElementById('food-title');

    const descriptionInput =
        document.getElementById('food-description');

    const priceInput =
        document.getElementById('food-price');

    const categorySelect =
        document.getElementById('food-category');

    if (
        !titleInput
        || !descriptionInput
        || !priceInput
        || !categorySelect
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

    const categoryUuid =
        categorySelect.value;

    if (!title) {
        displayAddMessage(
            'Le nom du plat est obligatoire.'
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

    if (!categoryUuid) {
        displayAddMessage(
            'Veuillez sélectionner une catégorie.'
        );

        return;
    }

    const categoryExists =
        categories.some(
            (category) =>
                category.uuid === categoryUuid
        );

    if (!categoryExists) {
        displayAddMessage(
            'La catégorie sélectionnée est invalide.'
        );

        return;
    }

    const token = getToken();

    if (!token) {
        displayAddMessage(
            'Vous devez être connecté pour ajouter un plat.'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/foods`,
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
                    categoryUuid
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
                || 'Catégorie ou restaurant introuvable.'
            );

            return;
        }

        if (!response.ok) {
            displayAddMessage(
                data?.message
                || 'Impossible d\'ajouter le plat.'
            );

            return;
        }

        form.reset();

        displayAddMessage(
            'Plat ajouté avec succès.',
            'success'
        );

        await loadFoods(categories);

    } catch (error) {
        console.error(error);

        displayAddMessage(
            'Une erreur est survenue lors de l\'ajout du plat.'
        );
    }
}

/**
 * Modifie un plat.
 *
 * @param {string} uuid
 * @param {string} title
 * @param {string} description
 * @param {string} price
 * @param {string} categoryUuid
 * @returns {Promise<void>}
 */
async function updateFood(
    uuid,
    title,
    description,
    price,
    categoryUuid
) {
    if (!title) {
        window.alert(
            'Le nom du plat est obligatoire.'
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

    if (!categoryUuid) {
        window.alert(
            'Veuillez sélectionner une catégorie.'
        );

        return;
    }

    const token = getToken();

    if (!token) {
        window.alert(
            'Vous devez être connecté pour modifier un plat.'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/foods/${uuid}`,
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
                    categoryUuid
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
                || 'Plat ou catégorie introuvable.'
            );

            return;
        }

        if (!response.ok) {
            window.alert(
                data?.message
                || 'Impossible de modifier le plat.'
            );

            return;
        }

        displayMessage(
            'Plat modifié avec succès.',
            'success'
        );

        const categories =
            await loadCategories();

        renderCategoryOptions(categories);

        await loadFoods(categories);

    } catch (error) {
        console.error(error);

        window.alert(
            'Une erreur est survenue lors de la modification du plat.'
        );
    }
}

/**
 * Supprime un plat.
 *
 * @param {Object} food
 * @returns {Promise<void>}
 */
async function handleDeleteFood(food) {
    const confirmed =
        window.confirm(
            `Voulez-vous vraiment supprimer le plat "${food.title}" ?`
        );

    if (!confirmed) {
        return;
    }

    const token = getToken();

    if (!token) {
        window.alert(
            'Vous devez être connecté pour supprimer un plat.'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/foods/${food.uuid}`,
            {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Authorization':
                        `Bearer ${token}`
                }
            }
        );

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
                'Plat introuvable.'
            );

            return;
        }

        if (!response.ok) {
            let errorMessage =
                'Impossible de supprimer le plat.';

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
            'Plat supprimé avec succès.',
            'success'
        );

        const categories =
            await loadCategories();

        await loadFoods(categories);

    } catch (error) {
        console.error(error);

        window.alert(
            'Une erreur est survenue lors de la suppression du plat.'
        );
    }
}

/**
 * Initialise la page de gestion des plats.
 *
 * @returns {Promise<void>}
 */
export async function init() {
    const addForm =
        document.getElementById('food-add-form');

    const titleInput =
        document.getElementById('food-title');

    const descriptionInput =
        document.getElementById('food-description');

    const priceInput =
        document.getElementById('food-price');

    const categorySelect =
        document.getElementById('food-category');

    const foodsContainer =
        document.getElementById('foods-container');

    if (
        !addForm
        || !titleInput
        || !descriptionInput
        || !priceInput
        || !categorySelect
        || !foodsContainer
    ) {
        return;
    }

    try {
        const categories =
            await loadCategories();

        renderCategoryOptions(categories);

        addForm.addEventListener(
            'submit',
            (event) => handleAddFood(
                event,
                categories
            )
        );

        await loadFoods(categories);

    } catch (error) {
        console.error(error);

        displayMessage(
            error.message
            || 'Une erreur est survenue lors du chargement de la page.',
            'danger'
        );
    }
}