import deDE from '../../snippet/de-DE.json';
import enGB from '../../snippet/en-GB.json';
import './list'
import './modal'

const {Module} = Shopware;

Module.register('plc-shipping-service', {
    type: 'plugin',
    name: 'plc-shipping-service',
    title: 'plc.menu.shippingService',
    description: 'plc.menu.descriptionTextModule',
    color: '#ff3d58',
    position: 990,
    snippets: {
        'de-DE': deDE, 'en-GB': enGB
    },

    routes: {
        list: {
            component: 'shipping-services-list', path: 'list', meta: {
                parentPath: 'sw.settings.index.plugins'
            },
        }
    },

    settingsItem: {
        group: 'plugins', to: 'plc.shipping.service.list', iconComponent: 'post-logo-icon', backgroundEnabled: true
    }
});
