import template from './return-reason-list.html.twig';
import './style.scss';
import deDE from "../../../snippet/de-DE.json";
import enGB from "../../../snippet/en-GB.json";

const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('return-reason-list', {
    template,

    snippets: {
        'de-DE': deDE, 'en-GB': enGB
    },

    mixins: [Mixin.getByName('notification'), Mixin.getByName('listing')],

    inject: ['repositoryFactory', 'acl', 'numberRangeService'],

    data() {
        return {
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            isLoading: false,
            returnReasonEntries: null,
            limit: 25,
            returnReasonsModal: false,
            total: 0,
            showDeleteModal: false,
            createModal: false
        };
    },

    metaInfo() {
        return {
            title: this.$tc('plc.menu.returnReasons')
        };
    },

    computed: {
        returnReasonsRepository() {
            return this.repositoryFactory.create('plc_return_reasons');
        },

        returnReasonColumns() {
            return this.getColumns();
        },

        showReturnReasonsModal() {
            return !!this.returnReasonsModal;
        },

        showCreationModal() {
            return !!this.createModal;
        }
    },

    beforeRouteLeave(to, from, next) {
        if (this.returnReasonsModal) {
            this.closeReturnReasonsModal();
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
            criteria.addAssociation("translations")

            try {
                const result = await Promise.all([this.returnReasonsRepository.search(criteria)])

                const returnReasons = result[0]
                this.total = returnReasons.total;
                this.returnReasonEntries = returnReasons;
                this.isLoading = false;
            } catch {
                this.isLoading = false;
            }
        },

        getColumns() {
            return [{
                property: 'name', primary: true, label: this.$tc('plc.returnReasons.list.name'), allowResize: true,
            }, {
                property: 'technicalName', label: this.$tc('plc.returnReasons.list.technicalName'), allowResize: true,
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

            return this.returnReasonsRepository.delete(id).then(() => {
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

        openReturnReasonsModal(item) {
            this.returnReasonsModal = item;
        },

        closeReturnReasonsModal() {
            this.returnReasonsModal = false;
        },

        saveReturnReasonModal() {
            this.returnReasonsModal = false;
            this.getList()
        }
    }

});