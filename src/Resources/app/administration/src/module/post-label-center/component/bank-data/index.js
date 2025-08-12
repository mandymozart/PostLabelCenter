import deDE from '../../snippet/de-DE.json';
import enGB from '../../snippet/en-GB.json';
import './list'
import './modal'

const {Module} = Shopware;

Module.register('plc-bank-data', {
    type: 'plugin',
    name: 'plc-bank-data',
    title: 'plc.menu.bankData',
    description: 'plc.menu.descriptionTextModule',
    color: '#ff3d58',
    position: 990,
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'bank-data-list',
            path: 'list',
            meta: {
                parentPath: 'sw.settings.index.plugins'
            },
        }
    },

    settingsItem: {
        group: 'plugins',
        to: 'plc.bank.data.list',
        iconComponent: 'post-logo-icon',
        backgroundEnabled: true
    }
});
