import template from "./shipping-document-tab.html.twig";
import './shipping-document-tab.scss';

const {Component, Mixin, Context} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('shipping-document-tab', {
    template,

    inject: [
        'repositoryFactory',
        'systemConfigApiService',
        'loginService',
        'numberRangeService'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
        Mixin.getByName('plc-helper')
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
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            isLoading: false,
            syncService: null,
            limit: 25,
            page: 1,
            postLabelEntries: null,
            createLabelModal: false,
            httpClient: null,
            trackingUrl: null,
            total: 0
        };
    },

    created() {
        this.createdComponent();
    },

    mounted() {
        this.createdComponent();
    },

    computed: {
        orderLabelsRepository() {
            return this.repositoryFactory.create('plc_order_labels');
        },

        orderLabelsColumns() {
            return this.getColumns();
        },

        showCreationModal() {
            return !!this.createLabelModal;
        }
    },

    beforeRouteLeave(to, from, next) {
        if (this.createLabelModal) {
            this.closeCreateModal();
        }

        this.$nextTick(() => {
            next();
        });
    },

    methods: {
        createdComponent() {
            this.syncService = Shopware.Service('syncService');
            this.httpClient = this.syncService.httpClient;
        },

        async getList() {
            this.isLoading = true;

            const criteria = new Criteria(this.page, this.limit);
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));
            criteria.addFilter(Criteria.equals("orderId", this.orderId));

            try {
                const result = await Promise.all([
                    this.orderLabelsRepository.search(criteria)
                ])

                this.total = result[0].total;
                this.postLabelEntries = result[0];
                this.isLoading = false;
            } catch {
                this.isLoading = false;
            }
        },

        getColumns() {
            return [
                {
                    property: 'createdAt',
                    primary: true,
                    label: this.$tc('plc.order.labels.createdAt'),
                    allowResize: true,
                },
                {
                    property: 'name',
                    label: this.$tc('plc.order.labels.fileName'),
                    allowResize: true,
                },
                {
                    property: 'shippingDocuments',
                    label: this.$tc('plc.order.labels.shippingDocuments'),
                    allowResize: true,
                },
                {
                    property: 'atTrackingNumber',
                    label: this.$tc('plc.order.labels.trackingAT'),
                    allowResize: true,
                },
                {
                    property: 'intTrackingNumber',
                    label: this.$tc('plc.order.labels.trackingINT'),
                    allowResize: true,
                },
                {
                    property: 'downloaded',
                    label: this.$tc('plc.order.labels.downloaded'),
                    allowResize: true,
                }
            ]
        },

        openCreateModal() {
            this.createLabelModal = true;
        },

        saveCreateModal() {
            this.createLabelModal = false;
            this.getList()
        },

        closeCreateModal() {
            this.createLabelModal = false;
        },

        async getLabelPdf(item, shippingDocument = false) {
            if (this.orderId === null || item.documentId === null) {
                return;
            }

            this.createNotificationInfo({
                message: this.$tc('plc.modal.pdfLabelStartingSoon'),
            });

            let payload = {
                "orderId": this.orderId, "documentId": item.documentId, "pdfLabelId": item.id
            };

            if (shippingDocument) {
                payload = {
                    "orderId": this.orderId,
                    "documentId": item.documentId,
                    "pdfLabelId": item.id,
                    "shippingContent": true
                };
            }

            this.isLoading = true;

            return this.httpClient.post(
                '/plc/shipping-data', payload,
                {
                    headers: this.syncService.getBasicHeaders()
                },
            ).then((response) => {
                if (response.status === 200) {
                    const linkSource = `data:application/pdf;base64,${response.data.data}`;
                    const downloadLink = document.createElement("a");
                    let fileName = (item.name === "RETURN_LABEL") ?
                        this.$tc('plc.order.download.returnLabel') + this.formatDate(item.createdAt, true) + ".pdf" : this.$tc('plc.order.download.shippingLabel') + this.formatDate(item.createdAt, true) + ".pdf";

                    if (shippingDocument) {
                        fileName = this.$tc('plc.order.download.shippingDocuments') + this.formatDate(item.createdAt, true) + ".pdf";
                    }
                    downloadLink.href = linkSource;
                    downloadLink.download = fileName;
                    downloadLink.click();

                    this.getList();
                }

                this.isLoading = false;
            });
        },

        async cancelShipment(labelId) {
            if (labelId === null) {
                return;
            }

            this.isLoading = true;

            return this.httpClient.post(
                '/plc/cancel-shipment',
                {
                    "orderId": this.orderId, "labelId": labelId
                },
                {
                    headers: this.syncService.getBasicHeaders()
                },
            ).then((response) => {
                if (response.status === 200) {
                    if (response.data.data === true) {
                        this.createNotificationSuccess({
                            message: this.$tc('plc.modal.cancelLabelSuccessful'),
                        });
                    } else {
                        this.createNotificationError({
                            message: this.$tc('plc.modal.errorDeletingLabel'),
                        })
                    }
                } else {
                    this.createNotificationError({
                        message: this.$tc('plc.modal.errorDeletingLabel'),
                    })
                }

                this.getList();
                this.isLoading = false;
            });
        },

        openTrackingUrl(trackingCode) {
            if (this.trackingUrl === null) {
                this.getTrackingUrl().then(trackingUrl => {
                    this.trackingUrl = trackingUrl
                    if (this.trackingUrl !== null) {
                        const route = this.trackingUrl + trackingCode;

                        window.open(route, '_blank');
                    }
                });
            } else {
                const route = this.trackingUrl + trackingCode;

                window.open(route, '_blank');
            }
        },

        getTrackingUrl() {
            return new Promise(resolve => {
                this.systemConfigApiService
                    .getValues('PostLabelCenter')
                    .then(response => {
                        resolve(response['PostLabelCenter.config.postTrackingUrl']);
                    });
            });
        },

        jsonDecode(value) {
            return JSON.parse(value)
        },

        updateTotal({total}) {
            this.total = total;
        },

        updateCriteria(criteria) {
            this.page = 1;
            this.filterCriteria = criteria;
        }
    }
});
