import template from './shipping-label-create-modal.html.twig';
import './style.scss';
import deDE from "../../snippet/de-DE.json";
import enGB from "../../snippet/en-GB.json";

const {Mixin} = Shopware;
const {Criteria} = Shopware.Data;

Shopware.Component.register('shipping-label-create-modal', {
    template,

    snippets: {
        'de-DE': deDE, 'en-GB': enGB
    },

    mixins: [Mixin.getByName('notification'), Mixin.getByName('plc-helper')],

    inject: ['systemConfigApiService', 'repositoryFactory', 'acl', 'feature'],

    data() {
        return {
            isLoading: true,
            deliveryAddress: null,
            returnAddress: null,
            shipperAddress: null,
            shippingService: null,
            lineItems: [],
            customsData: {
                description: null, returnOption: null, shippingType: null, packages: []
            },
            orderData: [],
            selectedLabelType: "both",
            bankData: null,
            shipperAddressId: null,
            returnAddressId: null,
            customsOptions: [],
            countryOptions: [],
            documentTypes: [],
            countryList: null,
            unitOptions: [],
            returnOptions: [],
            returnWays: [],
            shipmentDocumentEntries: [],
            defaultAddress: null,
            bankDataId: null,
            salesChannelId: null,
            syncService: null,
            httpClient: null,
            shippingProductId: null,
            activeTab: 'shippingData',
            enableShippingProductChange: true,
            pluginConfig: null
        }
    },

    props: {
        orderId: {
            type: String, required: false, default: null,
        }, isBulk: {
            type: Boolean, required: true, default: false,
        }, bulkOrderData: {
            type: Object, required: false, default: {}
        }
    },

    watch: {
        shippingProductId(value) {
            if (!value) {
                this.shippingProductId = null;
                this.shippingService = null;
            }
        }
    },

    computed: {
        addressDataRepository() {
            return this.repositoryFactory.create('plc_address_data', null);
        }, productRepository() {
            return this.repositoryFactory.create('product', null);
        },

        bankDataRepository() {
            return this.repositoryFactory.create('plc_bank_data', null);
        },

        orderRepository() {
            return this.repositoryFactory.create('order', null);
        },

        countryRepository() {
            return this.repositoryFactory.create('country', null);
        },

        shippingServiceRepository() {
            return this.repositoryFactory.create('plc_shipping_services', null);
        },

        getUnitOptions() {
            return this.unitOptions;
        },

        getReturnWays() {
            return this.returnWays;
        },

        getReturnOptions() {
            return this.returnOptions;
        },

        getShipmentDocumentEntries() {
            return this.shipmentDocumentEntries;
        },

        getCustomsOptions() {
            return this.customsOptions;
        },

        getDocumentTypes() {
            return this.documentTypes;
        },

        getCountryOptions() {
            return this.countryOptions;
        },

        modalTitle() {
            return this.$tc('plc.order.page.documentListTitle');
        },

        lineItemColumns() {
            return this.getLineItemColumns();
        },

        customsColumns() {
            return this.getCustomsColumns()
        },

        countryCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addSorting(Criteria.sort('name', 'ASC'));
            criteria.addFilter(Criteria.equals('active', true));

            return criteria;
        },

        salesChannelCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addFilter(Criteria.equals("active", true))
            criteria.addFilter(Criteria.equalsAny("type.iconName", ['regular-storefront', 'regular-shopping-basket']))
            criteria.addSorting(Criteria.sort('shortName', 'ASC'));
            return criteria;
        },

        shippingServiceCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addFilter(Criteria.not('OR', [Criteria.contains('featureList', "052"), Criteria.contains('featureList', "053")]));

            return criteria;
        },

        shipperAddressesCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addFilter(Criteria.equals("salesChannelId", this.orderData.salesChannelId))
            criteria.addFilter(Criteria.equalsAny("addressType", ["returnAndShipping", "shipping"]))
            criteria.addAssociation("bankData")
            criteria.addAssociation("country")
            criteria.addAssociation("salesChannel")

            return criteria;
        },

        returnAddressesCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addFilter(Criteria.equals("salesChannelId", this.orderData.salesChannelId))
            criteria.addFilter(Criteria.equalsAny("addressType", ["returnAndShipping", "return"]))
            criteria.addAssociation("bankData")
            criteria.addAssociation("country")
            criteria.addAssociation("salesChannel")

            return criteria;
        },

        salutationCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addSorting(Criteria.sort('displayName', 'ASC'));
            return criteria;
        },

        currencyCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addSorting(Criteria.sort('name', 'ASC'));
            return criteria;
        },

        bankDataCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addSorting(Criteria.sort('displayName', 'ASC'));
            return criteria;
        },

        getLabelTypeOptions() {
            return [{
                "value": "shipping_label", "label": "Versandlabel"
            }, {
                "value": "return_label", "label": "Retourenlabel"
            }, {
                "value": "both", "label": "Versand- und Retourenlabel"
            }];
        },

        createOptions() {
            return [{
                "value": "shipping", "label": this.$tc('plc.addressData.options.shipping')
            }, {
                "value": "return", "label": this.$tc('plc.addressData.options.return')
            }, {
                "value": "returnAndShipping", "label": this.$tc('plc.addressData.options.returnAndShipping')
            }];
        }
    },

    created() {
        this.createdComponent();
    },


    methods: {
        async getPluginConfig() {
            this.pluginConfig = await this.systemConfigApiService.getValues('PostLabelCenter.config', this.salesChannelId);
            this.selectedLabelType = this.pluginConfig['PostLabelCenter.config.defaultLabelType'];
        },

        async createdComponent() {
            this.isLoading = true;
            await this.getPluginConfig();

            if (this.isBulk) {
                if (this.bulkOrderData !== undefined && this.bulkOrderData[this.orderId]) {
                    let bulkData = this.bulkOrderData[this.orderId];

                    this.orderData = bulkData.orderData;
                    this.lineItems = bulkData.lineItems;
                    this.customsData.description = bulkData.customsData.description;
                    this.customsData.packages = bulkData.customsData.packages;
                    this.customsData.returnOption = bulkData.customsData.returnOption;
                    this.customsData.shippingType = bulkData.customsData.shippingType;
                    this.shippingService = bulkData.shippingService;
                    this.salesChannelId = bulkData.salesChannelId;

                    this.bankData = bulkData.bankData;
                    if (bulkData.bankData) {
                        this.bankDataId = bulkData.bankData.id;
                    }

                    this.shipperAddress = bulkData.shipperAddress;

                    if (bulkData.shipperAddress) {
                        this.shipperAddressId = bulkData.shipperAddress.id;
                    }

                    this.returnAddress = bulkData.returnAddress;
                    if (bulkData.returnAddress) {
                        this.returnAddressId = bulkData.returnAddress.id;
                    }

                    this.deliveryAddress = bulkData.deliveryAddress;
                }
            }

            this.syncService = Shopware.Service('syncService');
            this.httpClient = this.syncService.httpClient;
            await this.getOrderDeliveryAddress()

            this.isLoading = false;
        },

        setActiveItem(name) {
            this.activeTab = name;
        },

        createShippingLabel() {
            this.isLoading = true;
            this.createNotificationInfo({
                message: this.$tc("plc.order.postLabels.messages.creatingLabel"),
            });

            this.httpClient.post('/plc/create-manual-shipment', {
                "deliveryAddress": this.deliveryAddress,
                "returnAddress": this.returnAddress,
                "shipperAddress": this.shipperAddress,
                "shippingService": this.shippingService,
                "lineItems": this.lineItems,
                "customsData": this.customsData,
                "orderData": this.orderData,
                "selectedLabelType": this.selectedLabelType,
                "bankData": this.bankData,
                "salesChannelId": this.orderData.salesChannelId,
                "shippingProductId": this.shippingProductId
            }, {
                headers: this.syncService.getBasicHeaders()
            },).then((response) => {
                if (response.status === 200 && response.data.data) {
                    this.createNotificationSuccess({
                        message: this.$tc("plc.order.postLabels.messages.successCreatingLabel"),
                    });

                    this.isLoading = false;
                    this.$emit('modal-save');
                } else {
                    this.createNotificationError({
                        message: this.$tc("plc.order.postLabels.messages.errorCreatingLabel"),
                    });

                    if (response.data.message !== null) {
                        this.createNotificationError({
                            message: this.orderData.orderNumber + ": " + response.data.message,
                        });
                    }

                    this.isLoading = false;
                    this.$emit('modal-close');
                }
            });
        },

        async getOrderDeliveryAddress() {
            this.getCountryList();

            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals("id", this.orderId))
            criteria.addAssociation("deliveries")
            criteria.addAssociation("deliveries.shippingOrderAddress")
            criteria.addAssociation("deliveries.shippingMethod")
            criteria.addAssociation("lineItems.product")

            try {
                const result = await Promise.all([this.orderRepository.search(criteria)])

                if (result[0].total > 0) {
                    this.orderData = result[0].first();

                    if (!this.isBulk || (this.isBulk && this.bulkOrderData[this.orderId] === undefined)) {
                        this.deliveryAddress = this.orderData.deliveries[0]

                        this.orderData.lineItems.forEach((lineItem) => {
                            let product = (lineItem.product) ?? null;

                            if (product === null) {
                                const productCriteria = new Criteria();
                                productCriteria.addFilter(Criteria.equals("productNumber", lineItem.payload.productNumber))

                                this.productRepository.search(productCriteria, Shopware.Context.api).then(res => {
                                    product = (res.length > 0) ? res[0] : null;
                                });
                            }

                            this.lineItems.push({
                                "id": lineItem.id,
                                "packageNumber": 1,
                                "customsOptions": "1",
                                "units": 'PCE',
                                "countryOfOrigin": lineItem.customFields?.countryOfOrigin,
                                "hsTariffNumber": lineItem.customFields?.plc_customsTariffNumber,
                                "productId": lineItem.productId,
                                "productNumber": product?.productNumber ?? lineItem.payload.productNumber,
                                "name": lineItem.label,
                                "weight": product?.weight,
                                "quantity": lineItem.quantity,
                                "unitPrice": lineItem.unitPrice,
                                "defaultQuantity": lineItem.quantity
                            })
                        })
                    }

                    this.searchCurrentActiveAddress()

                    this.shippingProductId = this.deliveryAddress.shippingMethod.translated?.customFields?.plc_shipping_service ?? null
                    if (this.shippingProductId === null) {
                        this.shippingProductId = this.deliveryAddress.shippingMethod.customFields?.plc_shipping_service ?? null
                    }

                    await this.getPlcShippingServiceData();
                }
            } catch (error) {
                this.createErrorNotification({
                    message: error,
                });
            }
        },

        getPlcShippingServiceData() {
            if (this.shippingProductId) {
                const criteria = new Criteria();
                criteria.addFilter(Criteria.equals("id", this.shippingProductId))

                this.shippingServiceRepository.search(criteria).then((result) => {
                    if (result.total > 0) {
                        this.shippingService = result.first();
                        this.createCustomsOptions()
                    }
                });
            }
        },

        getThirdPartyId() {
            if (this.shippingService === null) {
                return false;
            }

            let thirdPartyId = this.jsonDecode(this.shippingService.shippingProduct, "thirdPartyID");

            return (thirdPartyId === "65" || thirdPartyId === "46")
        },

        displayOptionValue(value, array) {
            const foundOption = array.filter((option) => {
                return option.value === value
            });

            return foundOption.length > 0 ? foundOption[0].label : ""
        },

        duplicateEntry(item) {
            let countLineItem = 0;
            this.lineItems.forEach((lineItem) => {
                if (item.productId === lineItem.productId) {
                    countLineItem++;
                }
            })

            this.lineItems.push({
                "id": item.id.replace(/_.*$/, "") + "_" + countLineItem,
                "packageNumber": countLineItem + 1,
                "customsOptions": item.customsOptions,
                "units": item.units,
                "countryOfOrigin": item.countryOfOrigin,
                "hsTariffNumber": item.hsTariffNumber,
                "productNumber": item.productNumber,
                "productId": item.productId,
                "name": item.name,
                "weight": item.weight,
                "quantity": item.quantity,
                "unitPrice": item.unitPrice
            })
        },

        onEntryDelete(item) {
            this.lineItems = this.lineItems.filter((lineItem) => {
                return lineItem.id !== item.id
            });
        },

        onDeletePackage(item) {
            this.customsData.packages = this.customsData.packages.filter((pkg) => {
                return pkg !== item
            });
        },

        createCustomsOptions() {
            if (this.shippingService !== null) {
                const featureList = JSON.parse(this.shippingService.featureList);
                const filterPostWunschfiliale = featureList.filter(f => f.thirdPartyID === "052" || f.thirdPartyID === "053")

                if (filterPostWunschfiliale.length > 0) {
                    this.enableShippingProductChange = false;
                }

                const customsInformation = JSON.parse(this.shippingService.customsInformation)

                this.customsOptions = [];
                this.unitOptions = [];
                this.returnWays = [];
                this.shipmentDocumentEntries = [];
                this.returnOptions = [];

                this.createOptionArray(customsInformation.customsOption, this.customsOptions)
                this.createOptionArray(customsInformation.units, this.unitOptions)
                this.createOptionArray(customsInformation.returnway, this.returnWays)
                this.createOptionArray(customsInformation.shipmentDocumentEntry, this.shipmentDocumentEntries)
                this.createOptionArray(customsInformation.returnoptions, this.returnOptions)
            }
        },

        createOptionArray(data, optionField) {
            if (data !== null) {
                for (const index in data) {
                    let option = {
                        "value": index, "label": data[index],
                    };

                    if (!optionField.includes(option)) {
                        optionField.push(option);
                    }
                }
            }
        },

        getCountryList() {
            return this.httpClient.post('/plc/get-country-list', {}, {
                headers: this.syncService.getBasicHeaders()
            },).then((response) => {
                if (response.status === 200) {
                    this.countryList = response.data.data;
                    this.createOptionArray(this.countryList, this.countryOptions)
                }
            });
        },

        async getReturnAddress(id) {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals("id", id))

            try {
                const result = await Promise.all([this.addressDataRepository.search(criteria)])

                if (result[0].total > 0) {
                    this.returnAddress = result[0].first();
                }
            } catch (error) {
                this.createErrorNotification({
                    message: error,
                });
            }
        },

        async getShippingAddress(id) {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals("id", id))
            criteria.addAssociation("bankData")

            try {
                const result = await Promise.all([this.addressDataRepository.search(criteria)])

                if (result[0].total > 0) {
                    this.shipperAddress = result[0].first();
                    if (this.shipperAddress.bankDataId !== null) {
                        this.bankDataId = this.shipperAddress.bankDataId
                        this.bankData = this.shipperAddress.bankData
                    }
                }
            } catch (error) {
                this.createErrorNotification({
                    message: error,
                });
            }
        },

        async getBankData(id) {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals("id", id))

            try {
                const result = await Promise.all([this.bankDataRepository.search(criteria)])

                if (result[0].total > 0) {
                    this.bankData = result[0].first();
                }
            } catch (error) {
                this.createNotificationError({
                    message: error,
                });
            }
        },

        async getCountryData(addressData) {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals("id", addressData.countryId))

            try {
                const result = await Promise.all([this.countryRepository.search(criteria)])
                if (result[0].total > 0) {
                    addressData.country = result[0].first();
                }
            } catch (error) {
                this.createNotificationError({
                    message: error,
                });
            }
        },

        addCustomsColumn() {
            this.customsData.packages.push({
                "packageNumber": 1, "documentType": null, "documentNumber": null, "quantity": 1
            })
        },

        buildCriteriaAddress(addressType) {
            const criteria = new Criteria();
            criteria.addAssociation("country")
            criteria.addFilter(Criteria.equals("defaultAddress", true));
            criteria.addFilter(Criteria.equals("addressType", addressType));
            return criteria;
        },

        applyFilters(value1, value2) {
            this.criteria = new Criteria();
            this.criteria.filters = [];

            this.criteria.filters.push({
                field: 'salesChannelId', operator: 'equals', value: this.orderData.salesChannelId
            });

            let multiFilter = {
                connection: 'OR', filters: [{field: 'addressType', operator: 'equals', value: value1}, {
                    field: 'addressType', operator: 'equals', value: value2
                }]
            }

            return this.criteria.filters.push(multiFilter);
        },

        async searchCurrentActiveAddress() {
            const criteria = this.buildCriteriaAddress("returnAndShipping");

            const result = await Promise.all([this.addressDataRepository.search(criteria)])

            if (result[0].total > 0) {
                this.shipperAddressId = result[0].first().id
                this.returnAddressId = result[0].first().id
            } else {
                const returnCriteria = this.buildCriteriaAddress("return");

                const resultReturnAddress = await Promise.all([this.addressDataRepository.search(returnCriteria)])

                if (resultReturnAddress[0].total > 0) {
                    this.returnAddressId = resultReturnAddress[0].first().id
                }
                const shippingCriteria = this.buildCriteriaAddress("shipping");

                const resultShippingAddress = await Promise.all([this.addressDataRepository.search(shippingCriteria)])

                if (resultShippingAddress[0].total > 0) {
                    this.shipperAddressId = resultShippingAddress[0].first().id
                }

                let resultShippingAddressFirst;
                if (this.shipperAddressId === null) {
                    const shippingCriteriaFirst = this.applyFilters("shipping", "returnAndShipping");

                    resultShippingAddressFirst = await Promise.all([this.addressDataRepository.search(shippingCriteriaFirst)]);
                }

                if (resultShippingAddressFirst[0].total > 0) {
                    this.shipperAddressId = resultShippingAddressFirst[0].first().id
                }

                let resultReturnAddressFirst;
                if (this.returnAddressId === null) {
                    const returnCriteriaFirst = this.applyFilters("return", "returnAndShipping");

                    resultReturnAddressFirst = await Promise.all([this.addressDataRepository.search(returnCriteriaFirst)])
                }
                if (resultReturnAddressFirst[0].total > 0) {
                    this.shipperAddressId = resultReturnAddressFirst[0].first().id
                }
            }

            if (this.returnAddressId) {
                this.getReturnAddress(this.returnAddressId)
            }

            if (this.shipperAddressId) {
                this.getShippingAddress(this.shipperAddressId)
            }

        },


        getCustomsColumns() {
            return [{
                property: 'packageNumber',
                label: this.$tc('plc.order.postLabels.label.packageNumber'),
                allowResize: true,
                inlineEdit: 'number'
            }, {
                property: 'documentType',
                label: this.$tc('plc.order.postLabels.label.documentType'),
                dataIndex: 'label',
                inlineEdit: 'string'
            }, {
                property: 'documentNumber',
                label: this.$tc('plc.order.postLabels.label.documentNumber'),
                dataIndex: 'label',
                inlineEdit: 'string'
            }, {
                property: 'quantity',
                label: this.$tc('plc.order.postLabels.label.quantity'),
                dataIndex: 'label',
                inlineEdit: 'number'
            },]
        },

        getLineItemColumns() {
            return [{
                property: 'packageNumber', label: 'Paketnummer', allowResize: true, inlineEdit: 'number'
            }, {
                property: 'productNumber', label: 'ProductNumber', disabled: true, allowResize: true
            }, {
                property: 'name', label: 'Name', disabled: true, allowResize: true
            }, {
                property: 'quantity', label: 'Menge', allowResize: true, inlineEdit: 'number'
            }, {
                property: 'customsOptions', label: 'Zolloptionen', inlineEdit: 'string'
            }, {
                property: 'units', label: 'Einheit', inlineEdit: 'string'
            }, {
                property: 'countryOfOrigin', label: 'Ursprungsland', inlineEdit: 'string'
            }, {
                property: 'hsTariffNumber', label: 'HS Tariffnummer', allowResize: true, inlineEdit: 'string',
            }, {
                property: 'weight', label: 'Gewicht', allowResize: true,
            }, {
                property: 'unitPrice', label: 'Einzelpreis', allowResize: true
            }]
        },

        saveShippingLabelData() {
            this.$emit('modal-save', {
                "id": this.orderId,
                "data": {
                    "deliveryAddress": this.deliveryAddress,
                    "returnAddress": this.returnAddress,
                    "shipperAddress": this.shipperAddress,
                    "shippingService": this.shippingService,
                    "lineItems": this.lineItems,
                    "customsData": this.customsData,
                    "orderData": this.orderData,
                    "selectedLabelType": this.selectedLabelType,
                    "bankData": this.bankData,
                    "salesChannelId": this.orderData.salesChannelId,
                    "shippingProductId": this.shippingProductId
                }
            });
        },
    }
});
