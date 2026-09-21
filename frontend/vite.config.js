import { defineConfig } from 'vite';

import {
    cpSync,
    existsSync
} from 'node:fs';

import {
    resolve
} from 'node:path';


// Copie le dossier pages dans le build final
// en conservant sa structure.
function copyPagesPlugin() {

    return {
        name: 'copy-pages',

        closeBundle() {

            const source =
                resolve(
                    process.cwd(),
                    'pages'
                );

            const destination =
                resolve(
                    process.cwd(),
                    'dist',
                    'pages'
                );

            if (!existsSync(source)) {
                throw new Error(
                    'Le dossier pages est introuvable.'
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
    };
}


// Configuration de Vite
export default defineConfig({

    // Copie les pages HTML après la construction Vite.
    plugins: [
        copyPagesPlugin()
    ]

});