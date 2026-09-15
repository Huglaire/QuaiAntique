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
        document.getElementById('categories-message');

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
        document.getElementById('categories-message');

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
        document.getElementById('category-add-message');

    if (!messageContainer) {
        return;
    }

    messageContainer.textContent = message;
    messageContainer.className =
        `mt-3 text-${type}`;
}

/**
 * Crée la carte d'une catégorie.
 *
 * @param {Object} category
 * @returns {HTMLElement}
 */
function createCategoryCard(category) {
    const column =
        document.createElement('div');

    column.classList.add(
        'col-12',
        'col-md-6',
        'col-lg-4'
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

    body.classList.add(
        'card-body',
        'd-flex',
        'flex-column'
    );

    const title =
        document.createElement('h3');

    title.classList.add(
        'h5',
        'mb-3'
    );

    title.textContent =
        category.title || 'Sans titre';

    const actionsContainer =
        document.createElement('div');

    actionsContainer.classList.add(
        'd-flex',
        'gap-2',
        'flex-wrap',
        'mt-auto'
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
        () => handleEditCategory(category)
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
        () => handleDeleteCategory(category)
    );

    actionsContainer.append(
        editButton,
        deleteButton
    );

    body.append(
        title,
        actionsContainer
    );

    card.append(body);
    column.append(card);

    return column;
}

/**
 * Affiche les catégories dans la page.
 *
 * @param {Array} categories
 */
function renderCategories(categories) {
    const container =
        document.getElementById('categories-container');

    if (!container) {
        return;
    }

    container.replaceChildren();

    if (
        !Array.isArray(categories)
        || categories.length === 0
    ) {
        displayMessage(
            'Aucune catégorie n\'a encore été créée.',
            'info'
        );

        return;
    }

    hideMessage();

    const fragment =
        document.createDocumentFragment();

    categories.forEach((category) => {
        fragment.append(
            createCategoryCard(category)
        );
    });

    container.append(fragment);
}

/**
 * Récupère les catégories administrateur.
 *
 * @returns {Promise<void>}
 */
async function loadCategories() {
    const token = getToken();

    if (!token) {
        displayMessage(
            'Vous devez être connecté pour accéder aux catégories.',
            'danger'
        );

        return;
    }

    const container =
        document.getElementById('categories-container');

    if (container) {
        container.replaceChildren();
    }

    hideMessage();

    try {
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
                'Impossible de récupérer les catégories.'
            );
        }

        const categories =
            await response.json();

        renderCategories(categories);

    } catch (error) {
        console.error(error);

        displayMessage(
            'Une erreur est survenue lors du chargement des catégories.',
            'danger'
        );
    }
}

/**
 * Ajoute une catégorie.
 *
 * @param {SubmitEvent} event
 * @returns {Promise<void>}
 */
async function handleAddCategory(event) {
    event.preventDefault();

    const nameInput =
        document.getElementById('category-name');

    const form =
        event.currentTarget;

    if (!nameInput) {
        return;
    }

    displayAddMessage('');

    const title =
        nameInput.value.trim();

    if (!title) {
        displayAddMessage(
            'Le nom de la catégorie est obligatoire.'
        );

        return;
    }

    const token = getToken();

    if (!token) {
        displayAddMessage(
            'Vous devez être connecté pour ajouter une catégorie.'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/categories`,
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
                    title
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

        if (response.status === 409) {
            displayAddMessage(
                data?.message
                || 'Cette catégorie existe déjà.'
            );

            return;
        }

        if (!response.ok) {
            displayAddMessage(
                data?.message
                || 'Impossible d\'ajouter la catégorie.'
            );

            return;
        }

        form.reset();

        displayAddMessage(
            'Catégorie ajoutée avec succès.',
            'success'
        );

        await loadCategories();

    } catch (error) {
        console.error(error);

        displayAddMessage(
            'Une erreur est survenue lors de l\'ajout de la catégorie.'
        );
    }
}

/**
 * Modifie une catégorie.
 *
 * @param {Object} category
 * @returns {Promise<void>}
 */
async function handleEditCategory(category) {
    const newTitle =
        window.prompt(
            'Nouveau nom de catégorie :',
            category.title
        );

    if (newTitle === null) {
        return;
    }

    const title =
        newTitle.trim();

    if (!title) {
        window.alert(
            'Le nom de la catégorie est obligatoire.'
        );

        return;
    }

    const token = getToken();

    if (!token) {
        window.alert(
            'Vous devez être connecté pour modifier une catégorie.'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/categories/${category.uuid}`,
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
                    title
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
                || 'Catégorie introuvable.'
            );

            return;
        }

        if (response.status === 409) {
            window.alert(
                data?.message
                || 'Cette catégorie existe déjà.'
            );

            return;
        }

        if (!response.ok) {
            window.alert(
                data?.message
                || 'Impossible de modifier la catégorie.'
            );

            return;
        }

        displayMessage(
            'Catégorie modifiée avec succès.',
            'success'
        );

        await loadCategories();

    } catch (error) {
        console.error(error);

        window.alert(
            'Une erreur est survenue lors de la modification de la catégorie.'
        );
    }
}

/**
 * Supprime une catégorie.
 *
 * @param {Object} category
 * @returns {Promise<void>}
 */
async function handleDeleteCategory(category) {
    const confirmed =
        window.confirm(
            `Voulez-vous vraiment supprimer la catégorie "${category.title}" ?`
        );

    if (!confirmed) {
        return;
    }

    const token = getToken();

    if (!token) {
        window.alert(
            'Vous devez être connecté pour supprimer une catégorie.'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/categories/${category.uuid}`,
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
                'Catégorie introuvable.'
            );

            return;
        }

        if (!response.ok) {
            let errorMessage =
                'Impossible de supprimer la catégorie.';

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
            'Catégorie supprimée avec succès.',
            'success'
        );

        await loadCategories();

    } catch (error) {
        console.error(error);

        window.alert(
            'Une erreur est survenue lors de la suppression de la catégorie.'
        );
    }
}

/**
 * Initialise la page de gestion des catégories.
 *
 * @returns {Promise<void>}
 */
export async function init() {
    const addForm =
        document.getElementById('category-add-form');

    const nameInput =
        document.getElementById('category-name');

    const categoriesContainer =
        document.getElementById('categories-container');

    if (
        !addForm
        || !nameInput
        || !categoriesContainer
    ) {
        return;
    }

    addForm.addEventListener(
        'submit',
        handleAddCategory
    );

    await loadCategories();
}