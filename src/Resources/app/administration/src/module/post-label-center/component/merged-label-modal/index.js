import template from './merged-label-modal.html.twig';
import deDE from "../../snippet/de-DE.json";
import enGB from "../../snippet/en-GB.json";
import './style.scss';

const {Component, Mixin} = Shopware;

Component.register('merged-label-modal', {
    template,

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('plc-helper'),
    ],

    inject: ['repositoryFactory', 'acl', 'feature'],

    data() {
        return {
            isLoading: true,
            initialOrderList: [],
            generatedOrderLabels: []
        };
    },

    props: {
        orderList: {
            type: [Array, Object],
            required: true,
            default: null,
        }
    },

    computed: {
        orderRepository() {
            return this.repositoryFactory.create('order');
        },
        orderColumns() {
            return [
                {
                    property: 'orderNumber',
                    primary: true,
                    label: this.$tc('plc.mergedLabel.list.orderNumber'),
                    allowResize: true,
                },
                {
                    property: 'orderDateTime',
                    label: this.$tc('plc.mergedLabel.list.orderDateTime'),
                    allowResize: true,
                },
                {
                    property: 'deliveries[0].shippingOrderAddress.country.name',
                    label: this.$tc('plc.mergedLabel.list.targetCountry'),
                    allowResize: true,
                }
            ]
        },
        labelColumns() {
            return [
                {
                    property: 'orderNumber',
                    primary: true,
                    label: this.$tc('plc.mergedLabel.list.orderNumber'),
                    allowResize: true,
                },
                {
                    property: 'labelTypes',
                    label: this.$tc('plc.mergedLabel.list.labelTypes'),
                    allowResize: true,
                }
            ]
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initialOrderList = this.orderList;
            this.syncService = Shopware.Service('syncService');
            this.httpClient = this.syncService.httpClient;

            this.isLoading = false;
        },

        downloadZip() {
            this.createNotificationInfo({
                message: this.$tc("plc.mergedLabel.messages.downloadStartingSoon"),
            });

            this.isLoading = true;

            this.httpClient.post(
                '/plc/merged-label/download', {
                    "orders": JSON.stringify(this.initialOrderList)
                },
                {
                    headers: this.syncService.getBasicHeaders()
                },
            ).then((response) => {
                if (response.status === 200 && response.data) {
                    if (response.data.allOrders !== null && response.data.download !== null) {
                        this.generatedOrderLabels = response.data.allOrders
                        const linkSource = `data:application/zip;base64,${response.data.download}`;
                        const downloadLink = document.createElement("a");
                        downloadLink.href = linkSource;
                        downloadLink.download = response.data.filename;
                        downloadLink.click();
                    } else {
                        this.createNotificationInfo({
                            message: this.$tc("plc.mergedLabel.messages.noLabelsFound"),
                        });
                    }
                }

                this.isLoading = false;
            });
        }
    }
});
