const {Component} = Shopware;

Component.override('sw-system-config', {
    methods: {
        hasMapInheritanceSupport(element) {
            const customComponentNames = [
                'config-delivery-state'
            ];

            const componentName = element.config ? element.config.componentName : undefined;

            if (customComponentNames.includes(componentName)) {
                return true;
            }

            return this.$super('hasMapInheritanceSupport', element);
        }
    }
});

