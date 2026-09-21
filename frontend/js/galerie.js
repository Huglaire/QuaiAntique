const API_URL = '/api';
const API_BASE_URL = '';

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
                'La galerie ne contient aucune photo pour le moment.';

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
            'Impossible de charger la galerie pour le moment.';

        galleryMessage.append(message);
    }
}

/**
 * Crée une carte représentant une photo.
 *
 * Le titre est placé dans une superposition
 * qui apparaît au survol de l'image.
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
        'gallery-card'
    );

    const imageContainer =
        document.createElement('div');

    imageContainer.classList.add(
        'gallery-image-container'
    );

    const image =
        document.createElement('img');

    image.classList.add(
        'gallery-image'
    );

    image.src =
        `${API_BASE_URL}${picture.imageUrl}`;

    image.alt =
        picture.title
        || 'Photo du Quai Antique';

    image.loading =
        'lazy';

    const overlay =
        document.createElement('div');

    overlay.classList.add(
        'gallery-overlay'
    );

    const title =
        document.createElement('h2');

    title.classList.add(
        'gallery-title'
    );

    title.textContent =
        picture.title
        || 'Quai Antique';

    overlay.append(title);

    imageContainer.append(
        image,
        overlay
    );

    card.append(
        imageContainer
    );

    column.append(card);

    return column;
}