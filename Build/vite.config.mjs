// Theme delivery for the standalone demo; Innesto's element assets need no build.
export default {
    base: '/_assets/vite/',
    build: {
        manifest: true,
        outDir: '.Build/public/_assets/vite',
        rollupOptions: {
            input: [
                'vendor/webconsulting/desiderio/Resources/Private/Assets/Main.entry.css',
                'vendor/webconsulting/desiderio/Resources/Private/Assets/Components.entry.js',
            ],
        },
    },
};
