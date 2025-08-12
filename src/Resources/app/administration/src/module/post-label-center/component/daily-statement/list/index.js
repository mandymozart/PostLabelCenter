import template from './daily-statement-list.html.twig';
import './style.scss';
import deDE from "../../../snippet/de-DE.json";
import enGB from "../../../snippet/en-GB.json";

const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('daily-statement-list', {
    template,

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
        Mixin.getByName('plc-helper')
    ],

    inject: ['repositoryFactory', 'acl', 'numberRangeService'],

    data() {
        return {
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            isLoading: false,
            dailyStatementEntries: null,
            limit: 25,
            dailyStatementModal: false,
            total: 0,
            showDeleteModal: false,
            page: 1
        };
    },

    metaInfo() {
        return {
            title: this.$tc('plc.menu.dailyStatement')
        };
    },

    computed: {
        dailyStatementRepository() {
            return this.repositoryFactory.create('plc_daily_statements');
        },

        dailyStatementColumns() {
            return this.getColumns();
        },

        showDailyStatementModal() {
            return !!this.dailyStatementModal;
        }
    },

    beforeRouteLeave(to, from, next) {
        if (this.dailyStatementModal) {
            this.closeDailyStatementModal();
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

            try {
                const result = await Promise.all([
                    this.dailyStatementRepository.search(criteria)
                ])

                this.total = result[0].total;
                this.dailyStatementEntries = result[0];
                this.isLoading = false;

            } catch {
                this.isLoading = false;
            }
        },

        getColumns() {
            return [
                {
                    property: 'pdfData',
                    label: this.$tc('plc.dailyStatement.list.pdfData'),
                    allowResize: true
                },
                {
                    property: 'plcDateAdded',
                    primary: true,
                    label: this.$tc('plc.dailyStatement.list.plcDateAdded'),
                    allowResize: true,
                },
                {
                    property: 'plcCreatedOn',
                    label: this.$tc('plc.dailyStatement.list.plcCreatedOn'),
                    allowResize: true
                },
                {
                    property: 'salesChannel',
                    label: this.$tc('plc.dailyStatement.list.salesChannel'),
                    allowResize: true
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

            return this.dailyStatementRepository.delete(id).then(() => {
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

        openDailyStatementModal() {
            this.dailyStatementModal = true;
        },

        closeDailyStatementModal() {
            this.dailyStatementModal = false;
        },

        saveDailyStatementModal() {
            this.dailyStatementModal = false;
            this.getList()
        },

        async getLabelPdf(base64Pdf) {
            const linkSource = `data:application/pdf;base64,${JSON.parse(base64Pdf)}`;
            const downloadLink = document.createElement("a");
            const fileName = "Tagesabschluss.pdf";
            downloadLink.href = linkSource;
            downloadLink.download = fileName;
            downloadLink.click();
        },
    }

});