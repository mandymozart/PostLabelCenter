const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.extend('config-delivery-state', 'sw-custom-field-type-entity', {
    props: {
        criteria: {
            type: Object,
            required: false,
            default() {
                const criteria = new Criteria(1, 100);

                criteria.addFilter(
                    Criteria.equals(
                        'stateMachine.technicalName',
                        'order_delivery.state'
                    )
                );

                return criteria;
            }
        },
        currentCustomField: {
            type: Object,
            required: false,
        },

        set: {
            type: Object,
            required: false,
        },
    }
});
