const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-custom-field-type-entity', {
    computed: {
        entityTypes() {
            const types = this.$super('entityTypes');

            types.push(
                {
                    label: this.$tc('plc.custom-field.entity.shipping-services'),
                    value: 'plc_shipping_services',
                    config: {
                        labelProperty: ['displayName'],
                    },
                }
            );

            return types;
        }
    }
});