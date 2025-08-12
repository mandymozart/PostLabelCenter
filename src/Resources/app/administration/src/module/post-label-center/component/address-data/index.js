import deDE from '../../snippet/de-DE.json';
import enGB from '../../snippet/en-GB.json';
import './list'
import './modal'

const {Module} = Shopware;

Module.register('plc-address-data', {
    type: 'plugin',
    name: 'plc-address-data',
    title: 'plc.menu.addressData',
    color: '#ff3d58',
    position: 989,
    snippets: {
        'de-DE': deDE, 'en-GB': enGB
    },

    routes: {
        list: {
            component: 'address-data-list', path: 'list', meta: {
                parentPath: 'sw.settings.index.plugins'
            },
        }
    },

    settingsItem: {
        group: 'plugins', to: 'plc.address.data.list', iconComponent: 'post-logo-icon', backgroundEnabled: true
    }
});
