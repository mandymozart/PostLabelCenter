import template from './address-data-list.html.twig';
import './style.scss';
import deDE from "../../../snippet/de-DE.json";
import enGB from "../../../snippet/en-GB.json";

const {Component, Mixin} = Shopware;

const {Criteria} = Shopware.Data;

Component.register('address-data-list', {
    template,

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing')
    ],

    inject: ['repositoryFactory', 'acl', 'numberRangeService'],

    data() {
        return {
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            isLoading: false,
            addressDataEntries: null,
            limit: 25,
            addressDataModal: false,
            total: 0,
            showDeleteModal: false
        };
    },

    metaInfo() {
        return {
            title: this.$tc('plc.menu.addressData')
        };
    },

    computed: {
        addressDataRepository() {
            return this.repositoryFactory.create('plc_address_data');
        },

        addressDataColumns() {
            return this.getColumns();
        },

        showAddressDataModal() {
            return !!this.addressDataModal;
        },

        showCreationModal() {
            return !!this.createModal;
        }
    },

    beforeRouteLeave(to, from, next) {
        if (this.addressDataModal) {
            this.closeAddressDataModal();
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
            criteria.addAssociation("country");
            criteria.addAssociation("salutation");
            criteria.addAssociation("bankData");
            criteria.addAssociation("salesChannel");

            try {
                const result = await Promise.all([
                    this.addressDataRepository.search(criteria)
                ])

                const addressData = result[0]
                this.total = addressData.total;
                this.addressDataEntries = addressData;
                this.isLoading = false;
            } catch {
                this.isLoading = false;
            }
        },

        getAddressTypeLabel(type) {
            const labels = {
                "shipping": this.$tc('plc.addressData.options.shipping'),
                "return": this.$tc('plc.addressData.options.return'),
                "returnAndShipping": this.$tc('plc.addressData.options.returnAndShipping')
            }

            return labels[type]
        },

        getColumns() {
            return [
                {
                    property: 'displayName',
                    primary: true,
                    label: this.$tc('plc.addressData.list.displayName'),
                    allowResize: true,
                },
                {
                    property: 'defaultAddress',
                    label: this.$tc('plc.addressData.list.defaultAddress'),
                    allowResize: true
                },
                {
                    property: 'salesChannel',
                    label: this.$tc('plc.addressData.list.salesChannel'),
                    allowResize: true
                },
                {
                    property: 'addressType',
                    allowResize: true,
                    label: this.$tc('plc.addressData.list.addressType'),
                },
                {
                    property: 'email',
                    label: this.$tc('plc.addressData.list.email'),
                    allowResize: true,
                },
                {
                    property: 'company',
                    label: this.$tc('plc.addressData.list.company'),
                    allowResize: true,
                },
                {
                    property: 'department',
                    label: this.$tc('plc.addressData.list.department'),
                    allowResize: true,
                    visible: false
                },
                {
                    property: 'firstName',
                    label: this.$tc('plc.addressData.list.firstName'),
                    allowResize: true,
                },
                {
                    property: 'lastName',
                    label: this.$tc('plc.addressData.list.lastName'),
                    allowResize: true,
                },
                {
                    property: 'street',
                    label: this.$tc('plc.addressData.list.street'),
                    allowResize: true,
                },
                {
                    property: 'city',
                    label: this.$tc('plc.addressData.list.city'),
                    allowResize: true,
                },
                {
                    property: 'zipcode',
                    label: this.$tc('plc.addressData.list.zipcode'),
                    allowResize: true,
                },
                {
                    property: 'country',
                    label: this.$tc('plc.addressData.list.country'),
                    allowResize: true,
                },
                {
                    property: 'bankData',
                    label: this.$tc('plc.addressData.list.bankData'),
                    allowResize: true,
                }
            ]
        },

        onDelete(id) {
            this.showDeleteModal = id;
        },

        onConfirmDelete(id) {
            this.showDeleteModal = false;

            return this.addressDataRepository.delete(id).then(() => {
                this.getList();
            });
        },

        openAddressDataModal(item) {
            this.addressDataModal = item;
        },

        closeAddressDataModal() {
            this.addressDataModal = false;
        },

        saveAddressDataModal() {
            this.addressDataModal = false;
            this.getList()
        },

        updateTotal({total}) {
            this.total = total;
        },

        updateCriteria(criteria) {
            this.page = 1;
            this.filterCriteria = criteria;
        },

        onChangeLanguage(languageId) {
            Shopware.State.commit('context/setApiLanguageId', languageId);
            this.getList();
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false
        }
    }
});
