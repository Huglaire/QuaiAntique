const API_URL = '/api';

/**
 * Affiche un message d'erreur.
 */
function showError(message) {
    const error = document.getElementById('signup-error');

    if (!error) {
        return;
    }

    error.textContent = message;
    error.classList.remove('d-none');
}

/**
 * Masque le message d'erreur.
 */
function hideError() {
    const error = document.getElementById('signup-error');

    if (!error) {
        return;
    }

    error.textContent = '';
    error.classList.add('d-none');
}

/**
 * Affiche un message de succès.
 */
function showSuccess(message) {
    const success = document.getElementById('signup-success');

    if (!success) {
        return;
    }

    success.textContent = message;
    success.classList.remove('d-none');
}

/**
 * Vérifie que le mot de passe respecte les règles de sécurité.
 */
function isPasswordSecure(password) {
    const hasMinimumLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecialCharacter = /[^A-Za-z0-9]/.test(password);

    return (
        hasMinimumLength
        && hasUppercase
        && hasNumber
        && hasSpecialCharacter
    );
}

/**
 * Met à jour l'affichage d'une règle de mot de passe.
 */
function updatePasswordRequirement(
    element,
    isValid
) {
    if (!element) {
        return;
    }

    if (isValid) {
        element.classList.remove('text-secondary');
        element.classList.add('text-success');
    } else {
        element.classList.remove('text-success');
        element.classList.add('text-secondary');
    }
}

/**
 * Crée et affiche les règles du mot de passe.
 */
function createPasswordRequirements(passwordInput) {
    if (!passwordInput) {
        return null;
    }

    // Évite de créer plusieurs fois le bloc de règles.
    const existingRequirements =
        document.getElementById(
            'password-requirements'
        );

    if (existingRequirements) {
        return existingRequirements;
    }

    const container = document.createElement('div');

    container.id = 'password-requirements';
    container.classList.add(
        'mt-2',
        'small',
        'd-none'
    );

    const title = document.createElement('div');

    title.textContent =
        'Votre mot de passe doit contenir :';

    title.classList.add(
        'fw-semibold',
        'mb-1'
    );

    container.appendChild(title);

    const list = document.createElement('ul');

    list.classList.add(
        'list-unstyled',
        'mb-0'
    );

    const requirements = [
        {
            id: 'password-requirement-length',
            text: 'Au moins 8 caractères'
        },
        {
            id: 'password-requirement-uppercase',
            text: 'Une lettre majuscule'
        },
        {
            id: 'password-requirement-number',
            text: 'Un chiffre'
        },
        {
            id: 'password-requirement-special',
            text: 'Un caractère spécial'
        }
    ];

    requirements.forEach((requirement) => {
        const item = document.createElement('li');

        item.id = requirement.id;
        item.textContent = `○ ${requirement.text}`;

        item.classList.add(
            'text-secondary'
        );

        list.appendChild(item);
    });

    container.appendChild(list);

    // Insère le bloc juste après le champ de mot de passe.
    passwordInput.insertAdjacentElement(
        'afterend',
        container
    );

    return container;
}

/**
 * Met à jour les indicateurs des règles du mot de passe.
 */
function updatePasswordRequirements(
    password
) {
    const container =
        document.getElementById(
            'password-requirements'
        );

    if (!container) {
        return;
    }

    const hasMinimumLength =
        password.length >= 8;

    const hasUppercase =
        /[A-Z]/.test(password);

    const hasNumber =
        /[0-9]/.test(password);

    const hasSpecialCharacter =
        /[^A-Za-z0-9]/.test(password);

    updatePasswordRequirement(
        document.getElementById(
            'password-requirement-length'
        ),
        hasMinimumLength
    );

    updatePasswordRequirement(
        document.getElementById(
            'password-requirement-uppercase'
        ),
        hasUppercase
    );

    updatePasswordRequirement(
        document.getElementById(
            'password-requirement-number'
        ),
        hasNumber
    );

    updatePasswordRequirement(
        document.getElementById(
            'password-requirement-special'
        ),
        hasSpecialCharacter
    );

    const allRequirementsValid =
        hasMinimumLength
        && hasUppercase
        && hasNumber
        && hasSpecialCharacter;

    if (allRequirementsValid) {
        container.classList.remove(
            'text-secondary'
        );

        container.classList.add(
            'text-success'
        );

        const title =
            container.querySelector(
                'div'
            );

        if (title) {
            title.textContent =
                'Mot de passe sécurisé :';
        }

        const items =
            container.querySelectorAll(
                'li'
            );

        items.forEach((item) => {
            item.textContent =
                `✓ ${item.textContent.substring(2)}`;
        });
    } else {
        container.classList.remove(
            'text-success'
        );

        container.classList.add(
            'text-secondary'
        );

        const title =
            container.querySelector(
                'div'
            );

        if (title) {
            title.textContent =
                'Votre mot de passe doit contenir :';
        }

        const items =
            container.querySelectorAll(
                'li'
            );

        items.forEach((item) => {
            if (item.textContent.startsWith('✓ ')) {
                item.textContent =
                    `○ ${item.textContent.substring(2)}`;
            }
        });
    }
}

/**
 * Initialise le formulaire d'inscription.
 */
export function init() {
    const form =
        document.getElementById(
            'signup-form'
        );

    if (!form) {
        return;
    }

    if (form.dataset.initialized === 'true') {
        return;
    }

    form.dataset.initialized = 'true';

    const passwordInput =
        document.getElementById(
            'password'
        );

    const passwordRequirements =
        createPasswordRequirements(
            passwordInput
        );

    if (passwordInput && passwordRequirements) {
        passwordInput.addEventListener(
            'input',
            () => {
                const password =
                    passwordInput.value;

                if (password.length === 0) {
                    passwordRequirements.classList.add(
                        'd-none'
                    );

                    return;
                }

                passwordRequirements.classList.remove(
                    'd-none'
                );

                updatePasswordRequirements(
                    password
                );
            }
        );
    }

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            hideError();

            const firstName =
                document
                    .getElementById('firstName')
                    .value
                    .trim();

            const lastName =
                document
                    .getElementById('lastName')
                    .value
                    .trim();

            const email =
                document
                    .getElementById('email')
                    .value
                    .trim();

            const password =
                document
                    .getElementById('password')
                    .value;

            const confirmPassword =
                document
                    .getElementById('confirmPassword')
                    .value;

            const guestNumber =
                Number(
                    document
                        .getElementById('guestNumber')
                        .value
                );

            const allergy =
                document
                    .getElementById('allergy')
                    .value
                    .trim();

            if (
                !firstName
                || !lastName
                || !email
                || !password
                || !confirmPassword
            ) {
                showError(
                    'Veuillez remplir tous les champs obligatoires.'
                );

                return;
            }

            if (!isPasswordSecure(password)) {
                showError(
                    'Le mot de passe doit contenir au moins 8 caractères, dont une majuscule, un chiffre et un caractère spécial.'
                );

                return;
            }

            if (password !== confirmPassword) {
                showError(
                    'Les mots de passe ne correspondent pas.'
                );

                return;
            }

            if (
                !Number.isInteger(guestNumber)
                || guestNumber < 1
                || guestNumber > 50
            ) {
                showError(
                    'Le nombre de personnes doit être compris entre 1 et 50.'
                );

                return;
            }

            try {
                const response =
                    await fetch(
                        `${API_URL}/register`,
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type':
                                    'application/json',
                                'Accept':
                                    'application/json'
                            },
                            body: JSON.stringify({
                                firstName,
                                lastName,
                                email,
                                password,
                                guestNumber,
                                allergy:
                                    allergy || null
                            })
                        }
                    );

                let data = {};

                try {
                    data =
                        await response.json();
                } catch {
                    data = {};
                }

                if (!response.ok) {
                    throw new Error(
                        data.message
                        ?? 'Une erreur est survenue lors de la création du compte.'
                    );
                }

                showSuccess(
                    'Votre compte a été créé avec succès.'
                );

                form.reset();

                if (passwordRequirements) {
                    passwordRequirements.classList.add(
                        'd-none'
                    );
                }

                window.setTimeout(
                    () => {
                        window.history.pushState(
                            {},
                            '',
                            '/connexion'
                        );

                        window.dispatchEvent(
                            new PopStateEvent(
                                'popstate'
                            )
                        );
                    },
                    1000
                );

            } catch (error) {
                showError(
                    error.message
                    ?? 'Une erreur est survenue lors de la création du compte.'
                );
            }
        }
    );
}