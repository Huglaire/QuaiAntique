import { defineConfig } from 'vite';

import {
    cpSync,
    existsSync
} from 'node:fs';

import {
    resolve
} from 'node:path';


// Copie un dossier dans le build final
// en conservant sa structure.
function copyDirectory(sourceDirectory, destinationDirectory) {

    const source =
        resolve(
            process.cwd(),
            sourceDirectory
        );

    const destination =
        resolve(
            process.cwd(),
            'dist',
            destinationDirectory
        );

    if (!existsSync(source)) {
        throw new Error(
            `Le dossier ${sourceDirectory} est introuvable.`
        );
    }

    cpSync(
        source,
        destination,
        {
            recursive: true
        }
    );
}


// Copie les ressources qui doivent conserver
// leur chemin actuel dans le frontend.
function copyStaticResourcesPlugin() {

    return {
        name: 'copy-static-resources',

        closeBundle() {

            // Conserve les pages HTML utilisées
            // par le routeur de l'application SPA.
            copyDirectory(
                'pages',
                'pages'
            );

            // Conserve les images utilisées directement
            // depuis les pages HTML.
            copyDirectory(
                'Photos',
                'Photos'
            );
        }
    };
}


// Configuration de Vite
export default defineConfig({

    // Copie les ressources après la construction Vite.
    plugins: [
        copyStaticResourcesPlugin()
    ]

});