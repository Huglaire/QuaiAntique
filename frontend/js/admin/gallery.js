const API_URL = 'http://127.0.0.1:8000/api';
const API_BASE_URL = 'http://127.0.0.1:8000';

/**
 * Initialise la gestion administrateur de la galerie.
 *
 * @returns {Promise<void>}
 */
export async function init() {
    const addForm =
        document.getElementById('gallery-add-form');

    const galleryContainer =
        document.getElementById('gallery-admin-container');

    const galleryMessage =
        document.getElementById('gallery-admin-message');

    if (
        !addForm
        || !galleryContainer
        || !galleryMessage
    ) {
        return;
    }

    addForm.addEventListener(
        'submit',
        handleAddPicture
    );

    await loadPictures();
}

/**
 * Récupère les photos de la galerie administrateur.
 *
 * @returns {Promise<void>}
 */
async function loadPictures() {
    const galleryContainer =
        document.getElementById('gallery-admin-container');

    const galleryMessage =
        document.getElementById('gallery-admin-message');

    if (!galleryContainer || !galleryMessage) {
        return;
    }

    galleryMessage.textContent =
        'Chargement des photos...';

    try {
        const response = await fetch(
            `${API_URL}/admin/pictures`,
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                }
            }
        );

        if (!response.ok) {
            throw new Error(
                'Impossible de récupérer les photos.'
            );
        }

        const pictures = await response.json();

        galleryContainer.replaceChildren();
        galleryMessage.replaceChildren();

        if (
            !Array.isArray(pictures)
            || pictures.length === 0
        ) {
            const message =
                document.createElement('p');

            message.classList.add(
                'text-muted'
            );

            message.textContent =
                'Aucune photo dans la galerie.';

            galleryMessage.append(message);

            return;
        }

        const fragment =
            document.createDocumentFragment();

        for (const picture of pictures) {
            fragment.append(
                createPictureCard(picture)
            );
        }

        galleryContainer.append(fragment);

    } catch (error) {
        console.error(error);

        galleryContainer.replaceChildren();
        galleryMessage.replaceChildren();

        const message =
            document.createElement('p');

        message.classList.add(
            'text-danger'
        );

        message.textContent =
            'Impossible de charger les photos.';

        galleryMessage.append(message);
    }
}

/**
 * Gère l'ajout d'une photo.
 *
 * @param {SubmitEvent} event
 * @returns {Promise<void>}
 */
async function handleAddPicture(event) {
    event.preventDefault();

    const form = event.currentTarget;

    const titleInput =
        document.getElementById('gallery-title');

    const imageInput =
        document.getElementById('gallery-image');

    const message =
        document.getElementById('gallery-add-message');

    if (
        !titleInput
        || !imageInput
        || !message
    ) {
        return;
    }

    message.textContent = '';

    const title =
        titleInput.value.trim();

    const image =
        imageInput.files[0];

    if (!title) {
        message.classList.add(
            'text-danger'
        );

        message.textContent =
            'Le titre est obligatoire.';

        return;
    }

    if (!image) {
        message.classList.add(
            'text-danger'
        );

        message.textContent =
            'Veuillez sélectionner une image.';

        return;
    }

    const formData =
        new FormData();

    formData.append(
        'title',
        title
    );

    formData.append(
        'image',
        image
    );

    try {
        const response = await fetch(
            `${API_URL}/admin/pictures`,
            {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: formData
            }
        );

        const data =
            await response.json();

        if (!response.ok) {
            throw new Error(
                data.message
                || 'Impossible d\'ajouter la photo.'
            );
        }

        message.classList.remove(
            'text-danger'
        );

        message.classList.add(
            'text-success'
        );

        message.textContent =
            'Photo ajoutée avec succès.';

        form.reset();

        await loadPictures();

    } catch (error) {
        console.error(error);

        message.classList.remove(
            'text-success'
        );

        message.classList.add(
            'text-danger'
        );

        message.textContent =
            error.message
            || 'Impossible d\'ajouter la photo.';
    }
}

/**
 * Crée la carte d'une photo dans l'administration.
 *
 * @param {Object} picture
 * @returns {HTMLElement}
 */
function createPictureCard(picture) {
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

    const image =
        document.createElement('img');

    image.classList.add(
        'card-img-top'
    );

    image.src =
        `${API_BASE_URL}${picture.imageUrl}`;

    image.alt =
        picture.title || 'Photo du restaurant';

    image.loading =
        'lazy';

    image.style.height =
        '220px';

    image.style.objectFit =
        'cover';

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
        picture.title || 'Sans titre';

    const editButton =
        document.createElement('button');

    editButton.type =
        'button';

    editButton.classList.add(
        'btn',
        'btn-outline-primary',
        'mb-2'
    );

    editButton.textContent =
        'Modifier le titre';

    editButton.addEventListener(
        'click',
        () => handleEditPicture(picture)
    );

    const deleteButton =
        document.createElement('button');

    deleteButton.type =
        'button';

    deleteButton.classList.add(
        'btn',
        'btn-outline-danger'
    );

    deleteButton.textContent =
        'Supprimer';

    deleteButton.addEventListener(
        'click',
        () => handleDeletePicture(picture)
    );

    body.append(
        title,
        editButton,
        deleteButton
    );

    card.append(
        image,
        body
    );

    column.append(card);

    return column;
}

/**
 * Modifie le titre d'une photo.
 *
 * @param {Object} picture
 * @returns {Promise<void>}
 */
async function handleEditPicture(picture) {
    const newTitle =
        window.prompt(
            'Nouveau titre :',
            picture.title
        );

    if (newTitle === null) {
        return;
    }

    const title =
        newTitle.trim();

    if (!title) {
        window.alert(
            'Le titre est obligatoire.'
        );

        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/pictures/${picture.id}`,
            {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify({
                    title
                })
            }
        );

        const data =
            await response.json();

        if (!response.ok) {
            throw new Error(
                data.message
                || 'Impossible de modifier la photo.'
            );
        }

        await loadPictures();

    } catch (error) {
        console.error(error);

        window.alert(
            error.message
            || 'Impossible de modifier la photo.'
        );
    }
}

/**
 * Supprime une photo.
 *
 * @param {Object} picture
 * @returns {Promise<void>}
 */
async function handleDeletePicture(picture) {
    const confirmed =
        window.confirm(
            `Voulez-vous vraiment supprimer "${picture.title}" ?`
        );

    if (!confirmed) {
        return;
    }

    try {
        const response = await fetch(
            `${API_URL}/admin/pictures/${picture.id}`,
            {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                }
            }
        );

        if (!response.ok) {
            let message =
                'Impossible de supprimer la photo.';

            try {
                const data =
                    await response.json();

                message =
                    data.message || message;

            } catch {
                // La réponse 204 ne contient aucun JSON.
            }

            throw new Error(message);
        }

        await loadPictures();

    } catch (error) {
        console.error(error);

        window.alert(
            error.message
            || 'Impossible de supprimer la photo.'
        );
    }
}

/**
 * Récupère le token JWT.
 *
 * @returns {string|null}
 */
function getToken() {
    return localStorage.getItem('jwt');
}