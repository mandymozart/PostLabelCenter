import template from './sw-order-list.html.twig';
import './sw-order-list.scss';

const {Component} = Shopware;

Component.override('sw-order-list', {
    template,

    data() {
        return {
            showBulkLabelModal: false,
            showMergedLabelModal: false
        }
    },

    computed: {
        displayBulkLabelModal() {
            return !!this.showBulkLabelModal;
        },

        displayMergedFileModal() {
            return !!this.showMergedLabelModal;
        },

        orderCriteria() {
            let criteria = this.$super('orderCriteria');
            criteria.addAssociation('addresses.country')

            return criteria
        }
    },

    beforeRouteLeave(to, from, next) {
        if (this.showBulkLabelModal) {
            this.closeBulkLabelModal();
        }

        if (this.showMergedLabelModal) {
            this.closeMergedFileModal();
        }

        this.$nextTick(() => {
            next();
        });
    },

    methods: {
        openBulkLabelModal() {
            this.showBulkLabelModal = this.selectionArray
        },

        closeBulkLabelModal() {
            this.showBulkLabelModal = false;
        },

        saveBulkLabelModal() {
            this.showBulkLabelModal = false;
        },
        openMergedLabelModal() {
            this.showMergedLabelModal = this.selectionArray
        },

        closeMergedLabelModal() {
            this.showMergedLabelModal = false;
        },

        saveMergedLabelModal() {
            this.showMergedLabelModal = false;
        }
    }
});