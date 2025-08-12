import template from './bank-data-modal.html.twig';
import './style.scss';
import deDE from "../../../snippet/de-DE.json";
import enGB from "../../../snippet/en-GB.json";

const {Component, Mixin} = Shopware;

Component.register('bank-data-modal', {
    template,

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('plc-helper')
    ],

    inject: ['repositoryFactory', 'acl', 'feature'],

    data() {
        return {
            isLoading: false,
            bankDataObject: {
                displayName: null,
                accountHolder: null,
                bic: null,
                iban: null,
            }
        };
    },

    props: {
        bankDataEntity: {
            type: [Object, Boolean],
            required: false,
            default: null,
        }
    },

    created() {
        this.createdComponent();
    },

    computed: {
        modalTitle() {
            return (typeof this.bankDataEntity === "object") ? this.$tc('plc.bankData.editTitle') : this.$tc('plc.bankData.createTitle');
        },
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            this.syncService = Shopware.Service('syncService');
            this.httpClient = this.syncService.httpClient;

            if (typeof this.bankDataEntity === "object") {
                this.bankDataObject = {
                    displayName: this.bankDataEntity.displayName,
                    accountHolder: this.bankDataEntity.accountHolder,
                    bic: this.bankDataEntity.bic,
                    iban: this.bankDataEntity.iban,
                    id: this.bankDataEntity.id
                }
            }

            this.isLoading = false;
        },

        saveBankData() {
            this.isLoading = true;

            return this.httpClient.post(
                '/plc/bank-data/upsert', this.bankDataObject,
                {
                    headers: this.syncService.getBasicHeaders()
                },
            ).then((response) => {
                if (response.status === 200) {
                    if (response.data.data === true) {
                        this.createNotificationSuccess({
                            message: this.$tc(response.data.message),
                        });
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
            });

            this.isLoading = false;

        }
    }
});