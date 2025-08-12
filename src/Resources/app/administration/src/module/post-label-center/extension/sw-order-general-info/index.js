const {Component} = Shopware;
import labelExtension from "../plc-create-label-extension";

Component.override('sw-order-general-info', {
    methods: {
        onLeaveModalConfirm(docIds, sendMail = true) {
            if (this.currentActionName === "ship") {
                labelExtension(this);
            }

            return this.$super('onLeaveModalConfirm', docIds, sendMail);
        }
    }
});
