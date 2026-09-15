const API_URL = 'http://127.0.0.1:8000/api';
const API_BASE_URL = 'http://127.0.0.1:8000';

/**
 * Initialise la page de galerie.
 *
 * @returns {Promise<void>}
 */
export async function init() {
    const galleryContainer =
        document.getElementById('gallery-container');

    const galleryMessage =
        document.getElementById('gallery-message');

    if (!galleryContainer || !galleryMessage) {
        return;
    }

    galleryMessage.textContent =
        'Chargement de la galerie...';

    try {
        const response = await fetch(
            `${API_URL}/pictures`,
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
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

        if (!Array.isArray(pictures) || pictures.length === 0) {
            const message = document.createElement('p');

            message.classList.add(
                'text-muted'
            );

            message.textContent =
                'La galerie ne contient aucune photo pour le moment.';

            galleryMessage.append(message);

            return;
        }

        const fragment =
            document.createDocumentFragment();

        for (const picture of pictures) {
            const column =
                createPictureCard(picture);

            fragment.append(column);
        }

        galleryContainer.append(fragment);

    } catch (error) {
        console.error(error);

        galleryContainer.replaceChildren();
        galleryMessage.replaceChildren();

        const message = document.createElement('p');

        message.classList.add(
            'text-danger'
        );

        message.textContent =
            'Impossible de charger la galerie pour le moment.';

        galleryMessage.append(message);
    }
}

/**
 * Crée une carte représentant une photo.
 *
 * @param {Object} picture
 * @returns {HTMLElement}
 */
function createPictureCard(picture) {
    const column = document.createElement('div');

    column.classList.add(
        'col-12',
        'col-md-6',
        'col-lg-4'
    );

    const card = document.createElement('article');

    card.classList.add(
        'card',
        'h-100',
        'shadow-sm',
        'overflow-hidden'
    );

    const image = document.createElement('img');

    image.classList.add(
        'card-img-top'
    );

    image.src =
        `${API_BASE_URL}${picture.imageUrl}`;

    image.alt =
        picture.title || 'Photo du Quai Antique';

    image.loading = 'lazy';

    image.style.height = '280px';
    image.style.objectFit = 'cover';

    const cardBody = document.createElement('div');

    cardBody.classList.add(
        'card-body'
    );

    const title = document.createElement('h2');

    title.classList.add(
        'card-title',
        'h5',
        'mb-0'
    );

    title.textContent =
        picture.title || 'Quai Antique';

    cardBody.append(title);

    card.append(
        image,
        cardBody
    );

    column.append(card);

    return column;
}