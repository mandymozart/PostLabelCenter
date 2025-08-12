import template from './shipping-services-list.html.twig';
import './style.scss';
import deDE from "../../../snippet/de-DE.json";
import enGB from "../../../snippet/en-GB.json";

const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('shipping-services-list', {
    template,

    snippets: {
        'de-DE': deDE, 'en-GB': enGB
    },

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
        Mixin.getByName('plc-helper'),
    ],

    inject: ['repositoryFactory', 'acl', 'numberRangeService'],

    data() {
        return {
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            isLoading: false,
            shippingServiceEntries: null,
            limit: 25,
            shippingServicesModal: false,
            total: 0,
            showDeleteModal: false,
            page: 1
        };
    },

    metaInfo() {
        return {
            title: this.$tc('plc.menu.shippingService')
        };
    },

    computed: {
        shippingServicesRepository() {
            return this.repositoryFactory.create('plc_shipping_services');
        },

        shippingMethodRepository() {
            return this.repositoryFactory.create('shipping_method');
        },

        shippingServicesColumns() {
            return this.getColumns();
        },

        showShippingServicesModal() {
            return !!this.shippingServicesModal;
        },

        showCreationModal() {
            return !!this.createModal;
        }
    },

    beforeRouteLeave(to, from, next) {
        if (this.shippingServicesModal) {
            this.closeShippingServicesModal();
        }

        this.$nextTick(() => {
            next();
        });
    },

    methods: {
        async getList() {
            this.isLoading = true;

            const criteria = new Criteria(this.page, this.limit);
            criteria.setTerm(this.term);
            criteria.addAssociation("salesChannel")
            criteria.addAssociation("countries")

            try {
                const result = await Promise.all([this.shippingServicesRepository.search(criteria)])

                const shippingServices = result[0]
                this.total = shippingServices.total;
                this.shippingServiceEntries = shippingServices;
                this.isLoading = false;
            } catch {
                this.isLoading = false;
            }
        },

        getColumns() {
            return [{
                property: 'displayName',
                primary: true,
                label: this.$tc('plc.shippingServices.list.displayName'),
                allowResize: true,
            }, {
                property: 'salesChannel', label: this.$tc('plc.shippingServices.list.salesChannel'), allowResize: true,
            }, {
                property: 'countries', label: this.$tc('plc.shippingServices.list.countries'), allowResize: true,
            }, {
                property: 'countryCodes', label: this.$tc('plc.shippingServices.list.countryCodes'), allowResize: true,

            }, {
                property: 'shippingProduct',
                label: this.$tc('plc.shippingServices.list.shippingProduct'),
                allowResize: true,
            }, {
                property: 'featureList', label: this.$tc('plc.shippingServices.list.featureList'), allowResize: true,
            }]
        },

        onDelete(id) {
            this.showDeleteModal = id;
        },

        onChangeLanguage(languageId) {
            Shopware.State.commit('context/setApiLanguageId', languageId);
            this.getList();
        },

        onConfirmDelete(id) {
            this.showDeleteModal = false;

            return this.shippingServicesRepository.delete(id).then(() => {

                const criteria = new Criteria(this.page, this.limit);
                criteria.addFilter(Criteria.equals("customFields.plc_shipping_service", id));

                this.shippingMethodRepository.search(criteria).then((result) => {
                    //todo: foreach element, set customfield to null
                    if (result.total > 0) {
                        result.forEach((customFieldElement) => {
                            customFieldElement.customFields.plc_shipping_service = null;
                        });
                    }
                });

                this.getList();
            });
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        updateTotal({total}) {
            this.total = total;
        },

        updateCriteria(criteria) {
            this.page = 1;
            this.filterCriteria = criteria;
        },

        openShippingServicesModal(item) {
            this.shippingServicesModal = item;
        },

        closeShippingServicesModal() {
            this.shippingServicesModal = false;
        },

        saveShippingServicesModal() {
            this.shippingServicesModal = false;
            this.getList()
        }
    }
});
