import template from "./order-return-tab.html.twig";
import './order-return-tab.scss';

const {Component, Mixin, Context} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('order-return-tab', {
    template,

    inject: [
        'repositoryFactory',
        'systemConfigApiService',
        'loginService',
        'feature'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('plc-helper'),
    ],

    props: {
        orderId: {
            type: String,
            required: false,
            default: null,
        }
    },

    data() {
        return {
            isLoading: false,
            limit: 25,
            orderReturnData: null,
            lineItems: []
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        orderReturnDataRepository() {
            return this.repositoryFactory.create('plc_order_return_data');
        },

        lineItemColumns() {
            return this.getLineItemColumns();
        },
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            const criteria = new Criteria(this.page, this.limit);
            criteria.setTerm(this.term);
            criteria.addFilter(Criteria.equals("orderId", this.orderId));
            criteria.addAssociation("returnReason")

            this.orderReturnDataRepository.search(criteria).then((result) => {
                this.orderReturnData = result.first();
                this.total = result.total;

                if (this.orderReturnData !== null) {
                    this.lineItems = JSON.parse(this.orderReturnData.lineItems)
                }
            });

            this.isLoading = false;
        },

        getLineItemColumns() {
            return [
                {
                    property: 'productNumber',
                    label: this.$tc('plc.order.returnData.columns.productNumber'),
                    disabled: true,
                },
                {
                    property: 'name',
                    label: this.$tc('plc.order.returnData.columns.name'),
                    disabled: true,
                    allowResize: true
                },
                {
                    property: 'quantity',
                    label: this.$tc('plc.order.returnData.columns.quantity'),
                },
                {
                    property: 'unitPrice',
                    label: this.$tc('plc.order.returnData.columns.unitPrice'),
                }
            ]
        },
    }
});
