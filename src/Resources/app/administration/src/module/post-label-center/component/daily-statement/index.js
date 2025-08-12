import deDE from '../../snippet/de-DE.json';
import enGB from "../../snippet/en-GB.json";
import './list'
import './modal'

const {Module} = Shopware;

Module.register('plc-daily-statement', {
    type: 'plugin',
    name: 'plc-daily-statement',
    title: 'plc.menu.dailyStatement',
    description: 'plc.menu.descriptionTextModule',
    color: '#ff3d58',
    position: 990,
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'daily-statement-list',
            path: 'list',
            meta: {
                parentPath: 'sw.settings.index.plugins'
            },
        }
    },

    settingsItem: {
        group: 'plugins',
        to: 'plc.daily.statement.list',
        iconComponent: 'post-logo-icon',
        backgroundEnabled: true
    }
});
