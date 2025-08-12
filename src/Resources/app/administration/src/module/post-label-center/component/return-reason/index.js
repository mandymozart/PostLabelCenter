import deDE from "../../snippet/de-DE.json";
import enGB from "../../snippet/en-GB.json";
import './list'
import './modal'

const {Module} = Shopware;

Module.register('plc-return-reason', {
    type: 'plugin',
    name: 'plc-return-reason',
    title: 'plc.menu.returnReason',
    description: 'plc.menu.descriptionTextModule',
    color: '#ff3d58',
    position: 990,
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'return-reason-list',
            path: 'list',
            meta: {
                parentPath: 'sw.settings.index.plugins'
            },
        }
    },

    settingsItem: {
        group: 'plugins',
        to: 'plc.return.reason.list',
        iconComponent: 'post-logo-icon',
        backgroundEnabled: true
    }
});
