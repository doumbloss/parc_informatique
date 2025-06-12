const Encore = require('@symfony/webpack-encore');

// ✅ Configure l'environnement avant tout
Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enableSassLoader()
    .enablePostCssLoader()
;

module.exports = Encore.getWebpackConfig();
