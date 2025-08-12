import template from './daily-statement-modal.html.twig';
import './style.scss';
import deDE from "../../../snippet/de-DE.json";
import enGB from "../../../snippet/en-GB.json";

const {Mixin} = Shopware;
const {Criteria} = Shopware.Data;

Shopware.Component.register('daily-statement-modal', {
    template,

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    mixins: [
        Mixin.getByName('notification'),
    ],

    inject: ['repositoryFactory', 'acl', 'feature'],

    data() {
        return {
            isLoading: false,
            syncService: null,
            httpClient: null,
            salesChannelId: null,
            statementDate: null,
            datePickerConfig: {
                'altFormat': 'd.m.Y'
            }
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        modalTitle() {
            return this.$tc('plc.dailyStatement.createTitle');
        },


        salesChannelCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addFilter(Criteria.equals("active", true))
            criteria.addFilter(Criteria.equalsAny("type.iconName", ['regular-storefront', 'regular-shopping-basket']))
            criteria.addSorting(Criteria.sort('shortName', 'ASC'));

            return criteria;
        },
    },

    methods: {
        createdComponent() {
            this.syncService = Shopware.Service('syncService');
            this.httpClient = this.syncService.httpClient;
        },

        closeModal() {
            this._isDestroyed = true;
        },

        sendRequest() {
            if (this.salesChannelId === null) {
                return;
            }

            this.isLoading = true;

            return this.httpClient.post(
                '/plc/daily-statement',
                {
                    "salesChannelId": this.salesChannelId, "statementDate": this.statementDate
                },
                {
                    headers: this.syncService.getBasicHeaders()
                },
            ).then((response) => {
                if (response.status === 200) {
                    if (response.data.data !== false) {
                        this.createNotificationSuccess({
                            message: this.$tc(response.data.message),
                        });
                        if (response.data.data !== true) {
                            this.downloadPDF(response.data.data);
                        }
                    } else {
                        this.createNotificationError({
                            message: this.$tc(response.data.message),
                        });
                    }

                    this.isLoading = false;
                    this.$emit('modal-save');
                    this.$emit('modal-close');

                } else {
                    this.createNotificationError({
                        message: this.$tc('plc.modal.saveError'),
                    });
                }

                this.isLoading = false;
            });
        },

        downloadPDF(base64Pdf) {
            const linkSource = `data:application/pdf;base64,${base64Pdf}`;
            const downloadLink = document.createElement("a");
            const fileName = "Tagesabschluss.pdf";
            downloadLink.href = linkSource;
            downloadLink.download = fileName;
            downloadLink.click();
        }
    }
});