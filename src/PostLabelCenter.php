<?php declare(strict_types=1);

namespace PostLabelCenter;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class PostLabelCenter extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        $this->addCustomFields($installContext->getContext());
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->addCustomFields($updateContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->removeCustomFields($uninstallContext->getContext());
    }

    private function addCustomFields($context)
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        foreach (self::CUSTOM_FIELDS as $set => $data) {
            $criteria = new Criteria();
            $criteria->addAssociation("customFields");
            $criteria->addFilter(new EqualsFilter('name', $set));

            /** @var CustomFieldSetEntity $searchCustomFieldSet */
            $searchCustomFieldSet = $customFieldSetRepository->search($criteria, $context);

            if ($searchCustomFieldSet->getTotal() === 0) {
                $customFieldSetRepository->upsert([
                    [
                        'id' => Uuid::randomHex(),
                        'name' => $data['name'],
                        'config' => $data['config'],
                        'relations' => $data['relations'],
                        'customFields' => self::CUSTOM_FIELDS[$set]["customFields"]
                    ]
                ], $context);
            } else {
                $existingCustomFields = $searchCustomFieldSet->first()->getCustomFields();

                $currentCustomFields = self::CUSTOM_FIELDS[$set]["customFields"];

                $customFieldRepository = $this->container->get('custom_field.repository');


                foreach ($existingCustomFields as $customField) {
                    if (isset($currentCustomFields[$customField->getName()])) {
                        unset($currentCustomFields[$customField->getName()]);
                    }
                }

                if (!empty($currentCustomFields)) {
                    foreach ($currentCustomFields as $cf) {
                        $customFieldRepository->upsert([
                            [
                                'id' => Uuid::randomHex(),
                                'name' => $cf['name'],
                                'config' => $cf['config'],
                                'type' => $cf['type'],
                                'active' => true,
                                'customFieldSetId' => $searchCustomFieldSet->first()->getId()
                            ]
                        ], $context);
                    }
                }
            }
        }
    }

    private function removeCustomFields($context)
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        foreach (self::CUSTOM_FIELDS as $set => $data) {
            $criteria = new Criteria();
            $criteria->addAssociation("customFields");
            $criteria->addFilter(new EqualsFilter('name', $set));

            /** @var CustomFieldSetEntity $searchCustomFieldSet */
            $searchCustomFieldSet = $customFieldSetRepository->search($criteria, $context);

            if ($searchCustomFieldSet->getTotal() > 0) {
                $customFieldSetRepository->delete([[
                    "id" => $searchCustomFieldSet->first()->getId()
                ]], $context);
            }
        }


    }

    public const CUSTOM_FIELDS = [
        "plc_product" => [
            'name' => 'plc_product',
            'config' => [
                'label' => [
                    'en-GB' => 'PLC Product-Additions',
                    'de-DE' => 'PLC Produkt-Erweiterungen',
                    'de-AT' => 'PLC Produkt-Erweiterungen',
                ]
            ],
            'relations' => [[
                'entityName' => 'product'
            ]],
            'customFields' => [
                "plc_dangerousGoods" => [
                    'name' => 'plc_dangerousGoods',
                    'type' => 'bool',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Dangerous good?',
                            'de-DE' => 'Gefahrengut?',
                            'de-AT' => 'Gefahrengut?',
                        ],
                        'customFieldPosition' => 1,
                        'customFieldType' => 'checkbox',
                        'componentName' => 'sw-field',
                        'type' => 'checkbox'
                    ]
                ],
                'countryOfOrigin' => [
                    'name' => 'countryOfOrigin',
                    'type' => 'entity',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Country of origin',
                            'de-DE' => 'Herkunftsland',
                            'de-AT' => 'Herkunftsland'
                        ],
                        'customFieldPosition' => 3,
                        'customFieldType' => CustomFieldTypes::ENTITY,
                        'entity' => 'country',
                        "componentName" => "sw-entity-single-select",
                    ]
                ],
                'plc_fragile' => [
                    'name' => 'plc_fragile',
                    'type' => 'bool',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Fragile?',
                            'de-DE' => 'Zerbrechlich?',
                            'de-AT' => 'Zerbrechlich?',
                        ],
                        'customFieldPosition' => 2,
                        'customFieldType' => 'checkbox',
                        'componentName' => 'sw-field',
                        'type' => 'checkbox'
                    ]
                ],
                'plc_customsTariffNumber' => [
                    'name' => 'plc_customsTariffNumber',
                    'type' => 'text',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Customs Tariff Number',
                            'de-DE' => 'Zolltarif Nummer',
                            'de-AT' => 'ZollTarif Nummer',
                        ],
                        'customFieldPosition' => 4,
                        'customFieldType' => 'text',
                        "componentName" => "sw-field"
                    ]
                ]
            ]
        ],
        "plc_shipping_method" => [
            'name' => 'plc_shipping_method',
            'config' => [
                'label' => [
                    'en-GB' => 'PLC Shipping-Method-Additions',
                    'de-DE' => 'PLC Versandmethoden-Erweiterungen',
                    'de-AT' => 'PLC Versandmethoden-Erweiterungen',
                ]
            ],
            'relations' => [[
                'entityName' => 'shipping_method'
            ]],
            'customFields' => [
                'plc_shipping_service' => [
                    'name' => 'plc_shipping_service',
                    'type' => 'entity',
                    'config' => [
                        'label' => [
                            'en-GB' => 'PLC - Shipping Service',
                            'de-DE' => 'PLC - Versandprodukt',
                            'de-AT' => 'PLC - Versandprodukt'
                        ],
                        'customFieldPosition' => 1,
                        'customFieldType' => CustomFieldTypes::ENTITY,
                        'entity' => 'plc_shipping_services',
                        "componentName" => "sw-entity-single-select",
                        "labelProperty" => [
                            "displayName"
                        ]
                    ]
                ]
            ]
        ],
        "plc_order" => [
            'name' => 'plc_order',
            'config' => [
                'label' => [
                    'en-GB' => 'PLC Order-Additions',
                    'de-DE' => 'PLC Bestellungen-Erweiterungen',
                    'de-AT' => 'PLC Bestellungen-Erweiterungen',
                ]
            ],
            'relations' => [[
                'entityName' => 'order'
            ]],
            'customFields' => [
                'plc_automatic_label' => [
                    'name' => 'plc_automatic_label',
                    'type' => 'bool',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Initial Label created automatically?',
                            'de-DE' => 'Initiales Label automatisch erstellt?',
                            'de-AT' => 'Initiales Label automatisch erstellt?',
                        ],
                        'customFieldPosition' => 1,
                        'customFieldType' => 'checkbox',
                        'componentName' => 'sw-field',
                        'type' => 'checkbox'
                    ]
                ],
                'plc_insurance' => [
                    'name' => 'plc_insurance',
                    'type' => 'float',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Insurance Value',
                            'de-DE' => 'Versicherungswert',
                            'de-AT' => 'Versicherungswert'
                        ],
                        'numberType' => 'float',
                        'customFieldPosition' => 2,
                        'customFieldType' => 'number',
                    ]
                ]
            ]
        ]
    ];
}
