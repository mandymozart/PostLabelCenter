import template from './return-reason-modal.html.twig';
import './style.scss';
import deDE from "../../../snippet/de-DE.json";
import enGB from "../../../snippet/en-GB.json";

const {Component, Mixin} = Shopware;

Component.register('return-reason-modal', {
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
            returnReasonObject: null,
            technicalName: null,
            name: null,
        };
    },

    props: {
        returnReasonEntity: {
            type: [Object, Boolean],
            required: true,
        }
    },

    created() {
        this.createdComponent();
    },

    computed: {
        modalTitle() {
            return (typeof this.bankDataEntity === "object") ? this.$tc('plc.returnReasons.editTitle') : this.$tc('plc.returnReasons.createTitle');
        },
        returnReasonsRepository() {
            return this.repositoryFactory.create('plc_return_reasons');
        },
        returnReasonTranslationRepository() {
            return this.repositoryFactory.create('plc_return_reasons');
        },
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            this.returnReasonObject = this.returnReasonsRepository.create(Shopware.Context.api);

            this.syncService = Shopware.Service('syncService');
            this.httpClient = this.syncService.httpClient;

            if (typeof this.returnReasonEntity === "object") {
                this.technicalName = this.returnReasonEntity.technicalName;
                this.name = this.returnReasonEntity.name;

                this.returnReasonObject = {
                    id: this.returnReasonEntity.id,
                }
            }

            this.isLoading = false;
        },

        saveReason() {
            this.isLoading = true;

            this.returnReasonObject.technicalName = this.technicalName
            this.returnReasonObject.name = this.name

            return this.httpClient.post(
                '/plc/return-reason/upsert', {
                    "returnReason": this.returnReasonObject,
                    "translation": {
                        "languageId": Shopware.Context.api.language.id,
                        "name": this.name
                    }
                },
                {
                    headers: this.syncService.getBasicHeaders()
                },
            ).then((response) => {
                if (response.status === 200) {
                    if (response.data.data === true) {
                        this.createNotificationSuccess({
                            message: this.$tc(response.data.message),
                        });
                        this.$emit('modal-save');
                    } else {
                        this.createNotificationError({
                            message: this.$tc(response.data.message),
                        });
                    }
                } else {
                    this.createNotificationError({
                        message: this.$tc('plc.modal.saveError'),
                    });
                }

                this.isLoading = false;
            });
        }
    }
});