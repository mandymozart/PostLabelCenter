import template from './address-data-modal.html.twig';
import './style.scss';
import deDE from "../../../snippet/de-DE.json";
import enGB from "../../../snippet/en-GB.json";

const {Defaults, Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('address-data-modal', {
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
            addressTypeOptions: null,
            addressObject: {
                defaultAddress: null,
                salesChannelId: null,
                displayName: null,
                email: null,
                eoriNumber: null,
                salutationId: null,
                company: null,
                department: null,
                firstName: null,
                lastName: null,
                street: null,
                city: null,
                zipcode: null,
                countryId: null,
                phoneNumber: null,
                addressType: null,
                bankDataId: null,
            }
        };
    },

    props: {
        addressDataEntity: {
            type: [Object, Boolean],
            required: false,
            default: null,
        }
    },

    created() {
        this.createdComponent();
    },

    computed: {
        addressDataRepository() {
            return this.repositoryFactory.create('plc_address_data');
        },

        modalTitle() {
            return (typeof this.addressDataEntity === "object") ? this.$tc('plc.addressData.editTitle') : this.$tc('plc.addressData.createTitle');
        },

        salutationCriteria() {
            const criteria = new Criteria(1, 25);

            criteria.addFilter(Criteria.not('or', [
                Criteria.equals('id', Defaults.defaultSalutationId),
            ]));

            return criteria;
        },

        bankDataCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addSorting(Criteria.sort('displayName', 'ASC'));
            return criteria;
        },

        countryCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addSorting(Criteria.sort('name', 'ASC'));
            return criteria;
        },

        salesChannelCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addFilter(Criteria.equals("active", true))
            criteria.addFilter(Criteria.equalsAny("type.iconName", ['regular-storefront', 'regular-shopping-basket']))
            criteria.addSorting(Criteria.sort('shortName', 'ASC'));
            return criteria;
        },


        createOptions() {
            this.addressTypeOptions = [
                {
                    "value": "shipping",
                    "label": this.$tc('plc.addressData.options.shipping')
                },
                {
                    "value": "return",
                    "label": this.$tc('plc.addressData.options.return')
                },
                {
                    "value": "returnAndShipping",
                    "label": this.$tc('plc.addressData.options.returnAndShipping')
                }
            ];

            return this.addressTypeOptions;
        }
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            this.syncService = Shopware.Service('syncService');
            this.httpClient = this.syncService.httpClient;

            if (typeof this.addressDataEntity === "object") {
                this.addressObject = {
                    id: this.addressDataEntity.id,
                    displayName: this.addressDataEntity.displayName,
                    defaultAddress: this.addressDataEntity.defaultAddress,
                    eoriNumber: this.addressDataEntity.eoriNumber,
                    email: this.addressDataEntity.email,
                    salutationId: this.addressDataEntity.salutation.id,
                    company: this.addressDataEntity.company,
                    department: this.addressDataEntity.department,
                    firstName: this.addressDataEntity.firstName,
                    lastName: this.addressDataEntity.lastName,
                    street: this.addressDataEntity.street,
                    city: this.addressDataEntity.city,
                    zipcode: this.addressDataEntity.zipcode,
                    countryId: this.addressDataEntity.country.id,
                    phoneNumber: this.addressDataEntity.phoneNumber,
                    addressType: this.addressDataEntity.addressType,
                    bankDataId: this.addressDataEntity.bankData !== undefined ? this.addressDataEntity.bankData.id : null,
                    salesChannelId: this.addressDataEntity.salesChannel.id,
                }
            }

            this.isLoading = false;
        },

        saveAddressData() {
            this.isLoading = true;

            if (this.addressDataEntity.defaultAddress === true) {
                this.searchCurrentActiveAddress();
            }

            return this.httpClient.post(
                '/plc/address-data/upsert', this.addressObject,
                {
                    headers: this.syncService.getBasicHeaders()
                },
            ).then((response) => {
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
                    this.isLoading = false;
                }
            });
        },

        async searchCurrentActiveAddress() {
            let types = ["returnAndShipping"];
            if (this.addressDataEntity.addressType === "returnAndShipping") {
                types.push("return")
                types.push("shipping")
            } else if (this.addressDataEntity.addressType === "return") {
                types.push("return")
            } else if (this.addressDataEntity.addressType === "shipping") {
                types.push("shipping")
            }

            const criteria = new Criteria();
            criteria.addFilter(Criteria.not(
                'AND',
                [Criteria.equals('id', this.addressDataEntity.id)],
            ));
            criteria.addFilter(Criteria.equals("defaultAddress", true))
            criteria.addFilter(Criteria.equalsAny("addressType", types))

            try {
                const result = await Promise.all([
                    this.addressDataRepository.search(criteria)
                ])

                let foundEntities = result[0]

                if (foundEntities.total > 0) {
                    foundEntities.forEach((currentDefaultAddress) => {
                        currentDefaultAddress.defaultAddress = false;

                        this.addressDataRepository.save(currentDefaultAddress).then((response) => {
                            if (response !== undefined) {
                                this.$emit('modal-save');

                                return true;
                            }
                        });
                    });
                }

                return false;
            } catch (error) {
                return false;
            }
        }
    }

});