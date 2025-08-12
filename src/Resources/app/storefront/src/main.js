import WunschfilialePlugin from "./js/wunschfiliale/wunschfiliale.plugin"

const PluginManager = window.PluginManager;
PluginManager.register('Wunschfiliale', WunschfilialePlugin, '[data-post-wunschfiliale]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
