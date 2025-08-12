import template from './bulk-label-modal.html.twig';
import deDE from "../../snippet/de-DE.json";
import enGB from '../../snippet/en-GB.json';
import './style.scss';

const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('bulk-label-modal', {
    template,

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    mixins: [
        Mixin.getByName('notification'),
    ],

    inject: ['systemConfigApiService', 'repositoryFactory', 'acl', 'feature'],

    data() {
        return {
            isLoading: false,
            bulkOrderData: [],
            activeOrderList: [],
            newDeliveryState: null,
            createLabelModal: null,
            currentOrderId: null,
            selectedLabelType: "both",
            pdfDownload: true,
            customsEntries: null,
            failedOrders: [], //orders with failed labels
            successOrders: [], //orders with successful labels,
            successOrderList: []
        };
    },

    props: {
        orderList: {
            type: Array,
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
                    label: this.$tc('plc.bulkLabel.columns.orderNumber'),
                    allowResize: true,
                },
                {
                    property: 'targetCountry',
                    label: this.$tc('plc.bulkLabel.columns.targetCountry'),
                    allowResize: true,
                },
                {
                    property: 'deliveryState',
                    label: this.$tc('plc.bulkLabel.columns.deliveryState'),
                    allowResize: true,
                },
                {
                    property: 'customsInformation',
                    label: this.$tc('plc.bulkLabel.columns.customsInformation'),
                    allowResize: true,
                },
                {
                    property: 'savedLabel',
                    label: this.$tc('plc.bulkLabel.columns.savedLabel'),
                    allowResize: true,
                }
            ]
        },

        deliveryStateCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addFilter(Criteria.equals("stateMachine.technicalName", "order_delivery.state"))
            criteria.addSorting(Criteria.sort('name', 'ASC'));

            return criteria;
        },

        getLabelTypeOptions() {
            return [
                {
                    "value": "shipping_label",
                    "label": "Versandlabel"
                },
                {
                    "value": "return_label",
                    "label": "Retourenlabel"
                },
                {
                    "value": "both",
                    "label": "Versand- und Retourenlabel"
                }
            ];
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            this.activeOrderList = this.orderList;
            this.syncService = Shopware.Service('syncService');
            this.httpClient = this.syncService.httpClient;
            this.getPluginConfig()

            this.isLoading = false;
        },

        async getPluginConfig() {
            this.pluginConfig = await this.systemConfigApiService.getValues('PostLabelCenter.config', this.salesChannelId);
            this.selectedLabelType = this.pluginConfig['PostLabelCenter.config.defaultLabelType'];
            this.pdfDownload = !this.pluginConfig['PostLabelCenter.config.onlyDataimport'] ?? true;
        },

        async getOrderList(orderIds, orderList) {
            const criteria = new Criteria();
            criteria.setTerm(this.term);
            criteria.addFilter(Criteria.equalsAny(
                'id',
                orderIds,
            ));

            criteria.addAssociation('addresses.country');
            criteria.addAssociation('billingAddress');
            criteria.addAssociation('currency');
            criteria.addAssociation('deliveries.shippingOrderAddress');
            criteria.addAssociation('deliveries.stateMachineState');

            const result = await Promise.all([
                this.orderRepository.search(criteria)
            ])

            this[orderList] = result[0];
        },

        createBulkData() {
            if (this.selectedLabelType === null) {
                this.createNotificationError({
                    message: this.$tc("plc.bulkLabel.messages.missingFields"),
                });
                return;
            }

            this.isLoading = true;

            this.createNotificationInfo({
                message: this.$tc("plc.bulkLabel.messages.creatingLabel"),
            });

            this.httpClient.post(
                '/plc/bulk-shipment', {
                    "orders": JSON.stringify(this.activeOrderList),
                    "bulkOrderData": JSON.stringify(this.bulkOrderData),
                    "selectedLabelType": this.selectedLabelType,
                    "successOrders": JSON.stringify(this.successOrders),
                    "newDeliveryState": this.newDeliveryState
                },
                {
                    headers: this.syncService.getBasicHeaders()
                },
            ).then((response) => {
                if (response.status === 200 && response.data) {
                    this.failedOrders = response.data.failedOrders;
                    this.successOrders = response.data.successOrders;

                    let successOrderIds = this.successOrders.filter(function (order) {
                        return order.id
                    }).map(function (order) {
                        return order.id;
                    });

                    this.activeOrderList = this.activeOrderList.filter(
                        order => !successOrderIds.includes(order.id)
                    )

                    if (this.failedOrders.length > 0) {
                        this.failedOrders.forEach(order => {
                            this.createNotificationError({
                                message: this.$tc("plc.bulkLabel.messages.failedOrders", 1, {
                                    'orderNumber': order.orderNumber,
                                    'message': order.errorMessage
                                })
                            });
                        })
                    }

                    if (this.newDeliveryState !== null && response.data.failedTransitions !== null) {
                        this.createNotificationError({
                            message: this.$tc("plc.bulkLabel.messages.failedTransitions", 1, {
                                'orderNumbers': response.data.failedTransitions
                            })
                        });
                    }

                    if (successOrderIds.length > 0) {
                        this.getOrderList(successOrderIds, "successOrderList");
                    }
                } else {
                    this.createErrorNotification({
                        message: this.$tc(response.data.message)
                    });
                }

                this.isLoading = false;
            });
        },

        openCreateModal(orderId) {
            this.currentOrderId = orderId
            this.createLabelModal = true;
        },

        saveCreateModal(data) {
            this.isLoading = true;

            if (data) {
                const key = this.bulkOrderData.findIndex(bulkData => bulkData.id === data.id)
                if (key >= 0) {
                    this.bulkOrderData[key] = data;
                } else {
                    this.bulkOrderData.push(data);
                }
            }

            if (this.activeOrderList.length > 0) {
                const activeIds = this.activeOrderList.filter(function (order) {
                    return order.id
                }).map(function (order) {
                    return order.id;
                });

                this.getOrderList(activeIds, "activeOrderList");
            }

            if (this.successOrders.length > 0) {
                const successOrderIds = this.successOrders.filter(function (order) {
                    return order.id
                }).map(function (order) {
                    return order.id;
                });
                this.getOrderList(successOrderIds, "successOrderList");
            }

            this.currentOrderId = null;
            this.createLabelModal = false;
            this.isLoading = false;
        },

        closeCreateModal() {
            this.currentOrderId = null;
            this.createLabelModal = false;
        },

        checkCustomsData(orderId) {
            const bulkData = this.findBulkOrder(orderId)
            if (!bulkData) {
                return false;
            }

            if (!bulkData.customsData) {
                return false;
            }

            return bulkData.customsData.packages.length !== 0;
        },

        findBulkOrder(orderId) {
            if (this.bulkOrderData.length === 0) {
                return false;
            }

            const key = this.bulkOrderData.findIndex(bulkData => bulkData.id === orderId)
            return (key >= 0 && this.bulkOrderData[key].data) ? this.bulkOrderData[key].data : false;
        },

        downloadZip() {
            this.isLoading = true;

            this.createNotificationInfo({
                message: this.$tc("plc.bulkLabel.messages.downloadStartingSoon"),
            });

            this.httpClient.post(
                '/plc/bulk-shipment/download', {
                    "successOrders": JSON.stringify(this.successOrders)
                },
                {
                    headers: this.syncService.getBasicHeaders()
                },
            ).then((response) => {
                if (response.status === 200 && response.data && response.data.download) {
                    const linkSource = `data:application/zip;base64,${response.data.download}`;
                    const downloadLink = document.createElement("a");
                    downloadLink.href = linkSource;
                    downloadLink.download = response.data.fileName;
                    downloadLink.click();
                } else {
                    this.createNotificationInfo({
                        message: this.$tc("plc.bulkLabel.messages.errorDownloading"),
                    });
                }
            });

            this.isLoading = false;
        }
    }
});
