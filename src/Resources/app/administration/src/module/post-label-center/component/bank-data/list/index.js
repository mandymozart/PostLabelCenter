import template from './bank-data-list.html.twig';
import './style.scss';
import deDE from "../../../snippet/de-DE.json";
import enGB from "../../../snippet/en-GB.json";

const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

Shopware.Component.register('bank-data-list', {
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
            bankDataEntries: null,
            limit: 25,
            bankDataModal: false,
            total: 0,
            showDeleteModal: false,
        };
    },

    metaInfo() {
        return {
            title: this.$tc('plc.menu.bankData')
        };
    },

    computed: {
        bankDataRepository() {
            return this.repositoryFactory.create('plc_bank_data');
        },

        bankDataColumns() {
            return this.getColumns();
        },

        showBankDataModal() {
            return !!this.bankDataModal;
        }
    },

    beforeRouteLeave(to, from, next) {
        if (this.bankDataModal) {
            this.closeBankDataModal();
        }

        if (this.createModal) {
            this.closeCreateModal();
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

            try {
                const result = await Promise.all([
                    this.bankDataRepository.search(criteria)
                ])

                const bankData = result[0]
                this.total = bankData.total;
                this.bankDataEntries = bankData;
                this.isLoading = false;
            } catch {
                this.isLoading = false;
            }
        },

        getColumns() {
            return [
                {
                    property: 'displayName',
                    primary: true,
                    label: this.$tc('plc.bankData.list.displayName'),
                    allowResize: true,
                },
                {
                    property: 'accountHolder',
                    label: this.$tc('plc.bankData.list.accountHolder'),
                    allowResize: true,
                },
                {
                    property: 'bic',
                    label: this.$tc('plc.bankData.list.bic'),
                    allowResize: true,
                },
                {
                    property: 'iban',
                    label: this.$tc('plc.bankData.list.iban'),
                    allowResize: true,
                }
            ]
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

            return this.bankDataRepository.delete(id).then(() => {
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

        openBankDataModal(item) {
            this.bankDataModal = item;
        },

        closeBankDataModal() {
            this.bankDataModal = false;
        },

        saveBankDataModal() {
            this.bankDataModal = false;
            this.getList()
        }
    }
});