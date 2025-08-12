import template from './shipping-service-modal.html.twig';
import './style.scss';
import deDE from "../../../snippet/de-DE.json";
import enGB from "../../../snippet/en-GB.json";

const {Component, Mixin, Context} = Shopware;
const {Criteria, EntityCollection} = Shopware.Data;

Component.register('shipping-service-modal', {
    template,

    snippets: {
        'de-DE': deDE, 'en-GB': enGB
    },

    mixins: [Mixin.getByName('notification'),],

    inject: ['repositoryFactory', 'acl', 'feature'],

    props: {
        shippingServiceEntity: {
            type: [Object, Boolean],
            required: false,
            default: null,
        },
    },

    data() {
        return {
            isLoading: false,
            shippingServices: null,
            salesChannelId: null,
            selectedFeatures: [],
            selectedService: null,
            displayName: null,
            countries: null,
            syncService: null,
            httpClient: null,
            serviceOptions: [],
            shippingFeatures: [],
            newShippingService: null,
            responseMessage: null,
            entityId: null,
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        modalTitle() {
            return (typeof this.shippingServiceEntity === "object") ? this.$tc('plc.shippingServices.editTitle') : this.$tc('plc.shippingServices.createTitle');
        },

        countryRepository() {
            return this.repositoryFactory.create('country');
        },

        countryCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addSorting(Criteria.sort('name', 'ASC'));
            criteria.addFilter(Criteria.equals("active", true));

            return criteria;
        },

        salesChannelCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addFilter(Criteria.equals("active", true))
            criteria.addFilter(Criteria.equalsAny("type.iconName", ['regular-storefront', 'regular-shopping-basket']))
            criteria.addSorting(Criteria.sort('shortName', 'ASC'));

            return criteria;
        },

        shippingServicesOptions() {
            if (this.shippingServices.length === 0) {
                return [];
            }

            this.serviceOptions = []

            this.shippingServices.forEach((item) => {
                let option = {
                    "value": item.thirdPartyID, "label": item.name,
                };

                if (!this.serviceOptions.includes(option)) {
                    this.serviceOptions.push(option);
                }
            })

            return this.serviceOptions;
        },

        shippingFeaturesOptions() {
            if (this.shippingServices === null) {
                return [];
            }

            this.shippingFeatures = [];

            this.shippingServices.forEach((item) => {
                if (item.thirdPartyID === this.selectedService && item.featureList.length > 0) {
                    item.featureList.forEach((feature) => {
                        this.shippingFeatures.push({
                            "value": feature.thirdPartyID, "label": feature.name,
                        });
                    })
                }
            })

            return this.shippingFeatures;
        },
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            this.syncService = Shopware.Service('syncService');
            this.httpClient = this.syncService.httpClient;

            this.countries = new EntityCollection(this.countryRepository.route, this.countryRepository.entityName, Context.api);

            if (typeof this.shippingServiceEntity === "object") {
                this.displayName = this.shippingServiceEntity.displayName;
                this.salesChannelId = this.shippingServiceEntity.salesChannelId;
                this.countries = this.shippingServiceEntity.countries;
                this.entityId = this.shippingServiceEntity.id;

                if (this.countries && this.salesChannelId) {
                    this.updateAvailableServices().then(() => {
                        let currentService = JSON.parse(this.shippingServiceEntity.shippingProduct)

                        if (currentService) {
                            this.selectedService = currentService["thirdPartyID"]
                            let currentFeatures = JSON.parse(this.shippingServiceEntity.featureList)

                            if (currentFeatures && currentFeatures.length > 0) {
                                currentFeatures.forEach((feature) => {
                                    this.selectedFeatures.push(feature.thirdPartyID);
                                })
                            }
                        }

                        this.isLoading = false;
                    });
                }
            } else {
                this.isLoading = false;
            }
        },

        updateFeatureList() {
            this.selectedFeatures = [];
        },

        async updateAvailableServices() {
            this.isLoading = true;
            if (this.countries.length === 0 || this.salesChannelId === null) {
                this.selectedFeatures = [];
                this.selectedService = null;
                this.isLoading = false;

                return;
            }

            return this.httpClient.post('/plc/shipping-services', {
                "salesChannelId": this.salesChannelId, "countries": JSON.stringify(this.countries.map(c => c.iso))
            }, {
                headers: this.syncService.getBasicHeaders()
            },).then((response) => {
                if (response.status === 200 && response.data.data !== false) {
                    this.shippingServices = response.data.data;
                } else {
                    this.createNotificationInfo({
                        message: this.$tc(response.data.message),
                    });
                }

                this.selectedFeatures = [];
                this.selectedService = null;
                this.isLoading = false;
            });
        },

        async checkShippingFeatures() {
            return this.httpClient.post('/plc/shipping-services/features', {
                "featureList": JSON.stringify(this.selectedFeatures)
            }, {
                headers: this.syncService.getBasicHeaders()
            },).then((response) => {
                if (response.status === 200 && response.data.data === true) {
                    this.responseMessage = response.data.message;
                    this.saveShippingService();
                } else {
                    this.createNotificationError({
                        message: this.$tc(response.data.message),
                    });
                }

            });
        },

        saveShippingService() {
            const getData = (arr, value) => arr.filter(o => o.thirdPartyID === value);
            let serviceData = getData(this.shippingServices, this.selectedService)[0];
            let featureList = [];

            this.selectedFeatures.forEach((thirdPartyId) => {
                let feature = getData(serviceData.featureList, thirdPartyId)[0]
                featureList.push(feature)
            })

            let payload = {
                shippingProduct: JSON.stringify({
                    "orderID": serviceData.orderID, "thirdPartyID": serviceData.thirdPartyID, "name": serviceData.name,
                }),
                customsInformation: serviceData.customsInformation,
                featureList: JSON.stringify(featureList),
                displayName: this.displayName,
                countries: JSON.stringify(this.countries),
                salesChannelId: this.salesChannelId
            };

            if (this.entityId !== null) {
                payload.id = this.entityId
            }

            return this.httpClient.post('/plc/shipping-service/upsert', payload, {
                headers: this.syncService.getBasicHeaders()
            },).then((response) => {
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

                this.isLoading = false;
            });
        },

        onChange(collection) {
            this.isLoading = true;

            this.countries = collection;
            this.updateAvailableServices();

            this.isLoading = false;
        }
    }
});
