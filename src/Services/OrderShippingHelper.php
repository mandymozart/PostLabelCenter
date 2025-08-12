<?php

namespace PostLabelCenter\Services;

use Exception;
use PostLabelCenter\Core\Content\AddressData\AddressDataEntity;
use PostLabelCenter\Core\Content\BankData\BankDataEntity;
use PostLabelCenter\Core\Content\OrderLabels\OrderLabelsEntity;
use PostLabelCenter\Core\Content\OrderReturnData\OrderReturnDataCollection;
use PostLabelCenter\Core\Content\ShippingServices\ShippingServicesEntity;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Salutation\SalutationCollection;

class OrderShippingHelper
{
    private EntityRepository $orderRepository;
    private EntityRepository $currencyRepository;
    private EntityRepository $salutationRepository;
    private EntityRepository $countryRepository;
    private EntityRepository $orderDeliveryRepository;
    private EntityRepository $plcAddressDataRepository;
    private EntityRepository $plcOrderLabelsRepository;
    private EntityRepository $plcShippingServiceRepository;
    private EntityRepository $plcOrderReturnDataRepository;
    private string $shopVersion;

    public function __construct(EntityRepository $orderRepository,
                                EntityRepository $currencyRepository,
                                EntityRepository $salutationRepository,
                                EntityRepository $countryRepository,
                                EntityRepository $orderDeliveryRepository,
                                EntityRepository $plcAddressDataRepository,
                                EntityRepository $plcOrderLabelsRepository,
                                EntityRepository $plcShippingServiceRepository,
                                EntityRepository $plcOrderReturnDataRepository,
                                string           $shopVersion)
    {
        $this->orderRepository = $orderRepository;
        $this->currencyRepository = $currencyRepository;
        $this->salutationRepository = $salutationRepository;
        $this->countryRepository = $countryRepository;
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->plcAddressDataRepository = $plcAddressDataRepository;
        $this->plcOrderLabelsRepository = $plcOrderLabelsRepository;
        $this->plcShippingServiceRepository = $plcShippingServiceRepository;
        $this->plcOrderReturnDataRepository = $plcOrderReturnDataRepository;
        $this->shopVersion = $shopVersion;
    }

    /**
     * @throws \JsonException
     */
    public function mapShippingPdf($orderId, $pluginData, $overrideData = false)
    {
        $context = Context::createDefaultContext();

        /** @var OrderEntity $order */
        $order = $this->getOrderData($orderId);
        if (!$order) {
            return false;
        }

        $plcAddress = $this->getPlcAddresses($order->getSalesChannelId(), $context);
        if (!$plcAddress || is_null($plcAddress["senderAddress"] || is_null($plcAddress["returnAddress"]))) {
            return false;
        }

        /** @var AddressDataEntity $senderAddress */
        $senderAddress = $plcAddress["senderAddress"];

        if (is_null($senderAddress->getBankData())) {
            $senderAddress->setBankData(new BankDataEntity());
        }

        /** @var AddressDataEntity $returnAddress */
        $returnAddress = $plcAddress["returnAddress"];

        /** @var OrderDeliveryEntity $orderDeliveries */
        $orderDeliveries = $order->getDeliveries()->first();

        if ($overrideData) {
            $overrideDeliveryAddress = $overrideData["deliveryAddress"]["shippingOrderAddress"];

            $order->getOrderCustomer()->setEmail($overrideData["orderData"]["orderCustomer"]["email"]);

            $salutation = $this->getSalutation($overrideDeliveryAddress["salutationId"]);
            $orderDeliveries->getShippingOrderAddress()->setSalutation($salutation);
            $currency = $this->getCurrency($overrideData["orderData"]["currencyId"]);
            $order->setCurrency($currency);

            $country = $this->getCountry($overrideDeliveryAddress["countryId"]);
            $orderDeliveries->getShippingOrderAddress()->setCountry($country);

            $orderDeliveries->getShippingOrderAddress()->setFirstName($overrideDeliveryAddress["firstName"]);
            $orderDeliveries->getShippingOrderAddress()->setLastName($overrideDeliveryAddress["lastName"]);
            $orderDeliveries->getShippingOrderAddress()->setCompany($overrideDeliveryAddress["company"]);
            $orderDeliveries->getShippingOrderAddress()->setDepartment($overrideDeliveryAddress["department"]);
            $orderDeliveries->getShippingOrderAddress()->setStreet($overrideDeliveryAddress["street"]);
            $orderDeliveries->getShippingOrderAddress()->setCity($overrideDeliveryAddress["city"]);
            $orderDeliveries->getShippingOrderAddress()->setZipcode($overrideDeliveryAddress["zipcode"]);
            $orderDeliveries->getShippingOrderAddress()->setAdditionalAddressLine1($overrideDeliveryAddress["additionalAddressLine1"]);
            $orderDeliveries->getShippingOrderAddress()->setAdditionalAddressLine2($overrideDeliveryAddress["additionalAddressLine2"]);

            $senderAddress->getBankData()->setBic($overrideData["bankData"]["bic"]);
            $senderAddress->getBankData()->setAccountHolder($overrideData["bankData"]["accountHolder"]);
            $senderAddress->getBankData()->setIban($overrideData["bankData"]["iban"]);

            $senderAddress->setEoriNumber($overrideData["shipperAddress"]["eoriNumber"]);
            $senderAddress->setFirstName($overrideData["shipperAddress"]["firstName"]);
            $senderAddress->setLastName($overrideData["shipperAddress"]["lastName"]);
            $senderAddress->setEmail($overrideData["shipperAddress"]["email"]);
            $senderAddress->setCompany($overrideData["shipperAddress"]["company"]);
            $senderAddress->setDepartment($overrideData["shipperAddress"]["department"]);
            $senderAddress->setStreet($overrideData["shipperAddress"]["street"]);
            $senderAddress->setCity($overrideData["shipperAddress"]["city"]);
            $senderAddress->setZipcode($overrideData["shipperAddress"]["zipcode"]);
            $senderAddress->setPhoneNumber($overrideData["shipperAddress"]["phoneNumber"]);

            $shipperCountry = $this->getCountry($overrideData["shipperAddress"]["countryId"]);
            $senderAddress->setCountry($shipperCountry);

            $shipperSalutation = $this->getSalutation($overrideData["shipperAddress"]["countryId"]);
            $senderAddress->setSalutation($shipperSalutation);

            $returnAddress->setEoriNumber($overrideData["returnAddress"]["eoriNumber"]);
            $returnAddress->setFirstName($overrideData["returnAddress"]["firstName"]);
            $returnAddress->setLastName($overrideData["returnAddress"]["lastName"]);
            $returnAddress->setEmail($overrideData["returnAddress"]["email"]);
            $returnAddress->setCompany($overrideData["returnAddress"]["company"]);
            $returnAddress->setDepartment($overrideData["returnAddress"]["department"]);
            $returnAddress->setStreet($overrideData["returnAddress"]["street"]);
            $returnAddress->setCity($overrideData["returnAddress"]["city"]);
            $returnAddress->setZipcode($overrideData["returnAddress"]["zipcode"]);
            $returnAddress->setPhoneNumber($overrideData["returnAddress"]["phoneNumber"]);

            $shipperCountry = $this->getCountry($overrideData["returnAddress"]["countryId"]);
            $returnAddress->setCountry($shipperCountry);

            $shipperSalutation = $this->getSalutation($overrideData["returnAddress"]["countryId"]);
            $returnAddress->setSalutation($shipperSalutation);
        }

        $branchKey = $orderDeliveries->getShippingOrderAddress()->getCustomFields()["postOfficeBranchKey"] ?? "";
        $branchKeyType = $orderDeliveries->getShippingOrderAddress()->getCustomFields()["postOfficeBranchType"] ?? "";

        $shippingService = $this->getShippingService($orderDeliveries->getShippingMethod(), $overrideData["shippingProductId"] ?? false);
        if (!$shippingService) {
            return false;
        }

        $featureList = $this->getFeatureList($senderAddress->getBankData(),
            $order, $shippingService);

        if (!$featureList) {
            return false;
        }

        $printer = $this->mapPrinter($pluginData);
        $recipientAddress = $this->mapAddress($order->getOrderCustomer()->getEmail(), $orderDeliveries->getShippingOrderAddress(), $branchKey);
        $shipperAddress = $this->mapAddress($senderAddress->getEmail(), $senderAddress);
        $lineItems = $this->mapLineItems($order->getLineItems());

        $payload = [
            "orderId" => $order->getOrderNumber(),
            "shopId" => $order->getSalesChannelId(),
            "colloWeightList" => [$lineItems["weight"]],
            "ouRecipientAddress" => array_map('strval', $recipientAddress),
            "ouShipperAddress" => array_map('strval', $shipperAddress),
            "customDataBit1" => $featureList["customDataBit"],
            "deliveryServiceThirdPartyID" => $shippingService["shippingProduct"]["thirdPartyID"],
            "printer" => $printer,
            "documentType" => (isset($pluginData["defaultLabelType"])) ? strtoupper($pluginData["defaultLabelType"]) : "BOTH",
            "customerProduct" => "Shopware {$this->shopVersion}",
            "generateLabel" => !(isset($pluginData["onlyDataimport"]) && $pluginData["onlyDataimport"]),
            "branchKey" => $branchKey,
            "branchKeyType" => $branchKeyType,
            "featuresList" => $featureList["featureList"],
            "shipmentDocumentEntryList" => $overrideData ? $this->getShipmentDocuments($overrideData["customsData"]["packages"]) : [],
        ];

        if ($overrideData) {
            $payload["returnOptionId"] = $overrideData["customsData"]["returnOption"];
            $payload["returnModeId"] = $overrideData["customsData"]["shippingType"];
            $payload["customsDescription"] = $overrideData["customsData"]["description"] ?? "";

            $mappedReturnAddress = $this->mapAddress($returnAddress->getEmail(), $returnAddress);
            $payload["alternativeReturnOrgUnitAddress"] = array_map('strval', $mappedReturnAddress);
            $payload["documentType"] = strtoupper($overrideData["selectedLabelType"]);

            $colloRowRequests = $this->buildPackageLineItems($overrideData["lineItems"], $order->getCurrency()->getIsoCode());
            $payload["colloRowRequests"] = $colloRowRequests["colloRows"];
            $payload["colloWeightList"] = $colloRowRequests["colloWeights"];
        }

        return $payload;
    }

    private function buildPackageLineItems($lineItems, $currencyIsoCode)
    {
        $colloRows = [];
        $packages = [];
        $colloWeights = [];

        foreach ($lineItems as $item) {
            if (array_key_exists('packageNumber', $item)) {
                $packages[$item['packageNumber']][] = $item;
            }
        }

        ksort($packages, SORT_NUMERIC);

        foreach ($packages as $package) {
            $weight = 0;
            $colloArticleRows = [];

            foreach ($package as $lineItem) {
                $weight = 0;
                if (isset($lineItem["weight"])) {
                    $weight += ($lineItem["weight"] * $lineItem["quantity"]);
                }
                $articleRow = [
                    "articleName" => $lineItem["name"],
                    "articleNumber" => $lineItem["productNumber"],
                    "consumerUnitNetWeight" => isset($lineItem["weight"]) && $lineItem["weight"] > 0 ? $lineItem["weight"] : 0.1,
                    "countryOfOriginID" => $lineItem["countryOfOrigin"] ?? null,
                    "currencyID" => $currencyIsoCode,
                    "customsOptionID" => $lineItem["customsOptions"] ?? null,
                    "hSTariffNumber" => $lineItem["hsTariffNumber"] ?? null,
                    "quantity" => $lineItem["quantity"],
                    "unitID" => $lineItem["units"] ?? null,
                    "valueOfGoodsPerUnit" => $lineItem["unitPrice"],
                ];

                $colloArticleRows[] = $articleRow;
            }

            if (!empty($colloArticleRows)) {
                $colloRows[] = [
                    "colloArticleRows" => $colloArticleRows,
                    "height" => 0,
                    "width" => 0,
                    "length" => 0,
                    "weight" => $weight > 0 ? $weight : 0.1,
                ];

                $colloWeights[] = $weight;
            }
        }

        return [
            "colloRows" => $colloRows,
            "colloWeights" => $colloWeights
        ];
    }

    private function getShipmentDocuments($packages)
    {
        $documents = [];

        foreach ($packages as $package) {
            $documents[] = [
                "documentID" => $package["documentType"],
                "quantity" => $package["quantity"],
                "number" => $package["documentNumber"]
            ];
        }

        return $documents;
    }

    private function getShippingService(ShippingMethodEntity $shippingMethod, $overrideShippingService): array|false
    {
        $shippingMethodCustomFields = $shippingMethod->getCustomFields() ?? $shippingMethod->getTranslated()["customFields"];
        $shippingServiceId = $shippingMethodCustomFields["plc_shipping_service"] ?? null;

        if ($overrideShippingService) {
            $shippingServiceId = $overrideShippingService;
        }

        if (!is_null($shippingServiceId)) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter("id", $shippingServiceId));
            $searchShippingService = $this->plcShippingServiceRepository->search($criteria, Context::createDefaultContext());

            if ($searchShippingService->getTotal() === 0) {
                return false;
            }

            /** @var ShippingServicesEntity $shippingService */
            $shippingService = $searchShippingService->first();
            $features = json_decode($shippingService->getFeatureList(), true, 512, JSON_THROW_ON_ERROR);
            $shippingProduct = json_decode($shippingService->getShippingProduct(), true, 512, JSON_THROW_ON_ERROR);

            return [
                "service" => $shippingService,
                "features" => $features,
                "shippingProduct" => $shippingProduct
            ];
        }

        return false;
    }

    private function getFeatureList(?BankDataEntity $bankData, OrderEntity $order, $shippingService)
    {
        $features = $shippingService["features"];
        $newFeatureList = [];
        $customDataBit = false;

        if (isset($features)) {
            //cash on Delivery values
            $cashOnDelivery = ["006", "022"];

            //cash on Delivery values
            $higherInsurance = ["011", "063"];

            //cash on Delivery values
            $branchKeyFields = ["052", "053", "065", "066"];

            foreach ($features as $feature) {
                if (in_array($feature["thirdPartyID"], $cashOnDelivery, true)) {
                    if (is_null($bankData)) {
                        return false;
                    }
                    $feature["value1"] = $order->getAmountTotal();
                    $feature["value2"] = $order->getCurrency()->getIsoCode();
                    $feature["value3"] = $bankData->getIban() . "|" . $bankData->getBic() . "|" . $bankData->getAccountHolder();
                } else if (in_array($feature["thirdPartyID"], $higherInsurance, true)) {
                    $feature["value1"] = $order->getCustomFields()["plc_insurance"] ?? $order->getAmountTotal();
                    $feature["value2"] = $order->getCurrency()->getIsoCode();
                } else if ($feature["thirdPartyID"] === "142") {
                    $customDataBit = true;
                } else if (in_array($feature["thirdPartyID"], $branchKeyFields, true)) {
                    continue;
                }

                $newFeatureList[] = $feature;
            }
        }

        return [
            "featureList" => $newFeatureList,
            "customDataBit" => $customDataBit
        ];
    }

    function buildCriteriaAddress($salesChannelId, $defaultAddress, $addressType)
    {
        $criteria = new Criteria();
        $criteria->addAssociations(["country", "bankData", "salutation"]);
        $criteria->addFilter(new EqualsFilter("salesChannelId", $salesChannelId));
        $criteria->addFilter(new EqualsFilter("defaultAddress", $defaultAddress));
        $criteria->addFilter(new EqualsFilter("addressType", $addressType));

        return $criteria;
    }

    function applyMultiFilters($criteria, $salesChannelId, $addressTypeFilters)
    {
        $criteria->resetFilters();
        $criteria->addFilter(new EqualsFilter("salesChannelId", $salesChannelId));
        $multiFilter = new MultiFilter(MultiFilter::CONNECTION_OR, $addressTypeFilters);
        $criteria->addFilter($multiFilter);

        return $criteria;
    }

    /**
     * Used to get default address for the corresponding saleschannel
     * @param $salesChannelId
     * @param $context
     * @return false|mixed|null
     */
    private function getPlcAddresses($salesChannelId, $context)
    {
        $criteria = $this->buildCriteriaAddress($salesChannelId, true, "returnAndShipping");
        $searchAddresses = $this->plcAddressDataRepository->search($criteria, $context);

        if ($searchAddresses->getTotal() === 0) {
            $criteria = $this->buildCriteriaAddress($salesChannelId, true, "shipping");
            $searchSenderAddress = $this->plcAddressDataRepository->search($criteria, $context);

            $senderAddress = ($searchSenderAddress->getTotal() > 0) ? $searchSenderAddress->first() : null;

            if (is_null($senderAddress)) {
                $addressTypeFilters = [
                    new EqualsFilter("addressType", "shipping"),
                    new EqualsFilter("addressType", "returnAndShipping")
                ];
                $this->applyMultiFilters($criteria, $salesChannelId, $addressTypeFilters);

                $firstAvailableSenderAddress = $this->plcAddressDataRepository->search($criteria, $context);

                $senderAddress = ($firstAvailableSenderAddress->getTotal() > 0) ? $firstAvailableSenderAddress->first() : null;
            }

            $criteria->resetFilters();
            $criteria = $this->buildCriteriaAddress($salesChannelId, true, "return");
            $searchReturnAddress = $this->plcAddressDataRepository->search($criteria, $context);

            $returnAddress = ($searchReturnAddress->getTotal() > 0) ? $searchReturnAddress->first() : null;
            if (is_null($returnAddress)) {
                $addressTypeFiltersReturn = [
                    new EqualsFilter("addressType", "return"),
                    new EqualsFilter("addressType", "returnAndShipping")
                ];

                $this->applyMultiFilters($criteria, $salesChannelId, $addressTypeFiltersReturn);

                $firstAvailableReturnAddress = $this->plcAddressDataRepository->search($criteria, $context);

                $returnAddress = ($firstAvailableReturnAddress->getTotal() > 0) ? $firstAvailableReturnAddress->first() : null;

            }

            return [
                "senderAddress" => $senderAddress,
                "returnAddress" => $returnAddress
            ];
        }

        return [
            "senderAddress" => $searchAddresses->first(),
            "returnAddress" => $searchAddresses->first()
        ];
    }

    /**
     * Get Shopware Orderdata via Id
     * @param $orderId
     * @return false|mixed|null
     */
    public function getOrderData($orderId)
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addAssociations(["billingAddress.country", "deliveries.shippingOrderAddress.country",
            "lineItems.product", "currency", "deliveries.shippingMethod", "deliveries.shippingMethod.translations"]);
        $criteria->addFilter(new EqualsFilter("id", $orderId));

        $searchOrder = $this->orderRepository->search($criteria, $context);
        if ($searchOrder->getTotal() === 0) {
            return false;
        }

        /** @var OrderEntity $order */
        return $searchOrder->first();
    }

    /**
     * Get Shopware Orderdata via OrderNumber
     * @param $orderNumber
     * @return false|mixed|null
     */
    public function getOrderDataByNumber($orderNumber)
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("orderNumber", $orderNumber));
        $criteria->addAssociations(["deliveries.stateMachineState"]);

        $searchOrder = $this->orderRepository->search($criteria, $context);
        if ($searchOrder->getTotal() === 0) {
            return false;
        }

        /** @var OrderEntity $order */
        return $searchOrder->first();
    }

    /**
     * Map Shipping/Billingaddress to PLC needed format
     * @param $email
     * @param $address
     * @return array
     */
    private function mapAddress($email, $address, $branchKey = ""): array
    {
        return [
            "email" => $email ?? null,
            "company" => (method_exists($address, "getCompany") && $branchKey === "") ? $address->getCompany() : null,
            "department" => (method_exists($address, "getDepartment")) ? $address->getDepartment() : null,
            "firstname" => $address->getFirstName(),
            "lastname" => $address->getLastName(),
            "addressLine1" => $address->getStreet(),
            "addressLine2" => (method_exists($address, "getAdditionalAddressLine1")) ? $address->getAdditionalAddressLine1() : "",
            "houseNumber" => "",
            "phone" => $address->getPhoneNumber(),
            "postalCode" => $address->getZipcode(),
            "city" => $address->getCity(),
            "countryID" => $address->getCountry()?->getIso(),
        ];
    }

    /**
     * Generate correct mapping for Lineitems/Products
     * @param OrderLineItemCollection $lineItemCollection
     * @return array
     */
    private function mapLineItems(OrderLineItemCollection $lineItemCollection): array
    {
        $lineItems = [];
        $weight = 0;

        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItemCollection as $lineItem) {
            $lineItems[] = [
                "colloArticleRows" => [
                    "articleName" => $lineItem->getLabel(),
                    "articleNumber" => $lineItem->getPayload()["productNumber"],
                    "quantity" => $lineItem->getQuantity(),
                    "unitPrice" => $lineItem->getUnitPrice(),
                ],
                "height" => $lineItem?->getProduct()?->getHeight(),
                "length" => $lineItem?->getProduct()?->getLength(),
                "weight" => $lineItem?->getProduct()?->getWeight(),
                "width" => $lineItem?->getProduct()?->getWidth(),
            ];

            $weight += (float)$lineItem?->getProduct()?->getWeight();
        }

        return [
            "weight" => ($weight !== 0) ? $weight : 0.1,
            "lineItems" => $lineItems
        ];
    }

    private function mapPrinter($pluginData)
    {
        return [
            "labelFormatID" => $pluginData["labelFormat"] ?? "100x200",
            "languageID" => "pdf",
            "paperLayoutID" => $pluginData["paperLayout"] ?? "A4",
        ];
    }

    public function saveLabelAsDownloaded($labelId)
    {
        $context = Context::createDefaultContext();

        try {
            $this->plcOrderLabelsRepository->upsert([
                [
                    "id" => $labelId,
                    "downloaded" => true
                ]
            ], $context);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $salutationId
     * @return mixed|null
     */
    public function getSalutation($salutationId)
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("id", $salutationId));
        /** @var SalutationCollection $searchSalutation */
        $searchSalutation = $this->salutationRepository->search($criteria, $context);

        if ($searchSalutation->count() > 0) {
            return $searchSalutation->first();
        }

        return null;
    }

    /**
     * @param $currencyId
     * @return mixed|null
     */
    public function getCurrency($currencyId)
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("id", $currencyId));
        /** @var CurrencyCollection $searchCurrency */
        $searchCurrency = $this->currencyRepository->search($criteria, $context);

        if ($searchCurrency->count() > 0) {
            return $searchCurrency->first();
        }

        return null;
    }

    /**
     * @param $countryId
     * @return mixed|null
     */
    public function getCountry($countryId)
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("id", $countryId));
        /** @var CountryCollection $searchCountry */
        $searchCountry = $this->countryRepository->search($criteria, $context);

        if ($searchCountry->count() > 0) {
            return $searchCountry->first();
        }

        return null;
    }

    /**
     * @param $orderId
     * @param $responseData
     * @param $orderData
     * @param $manualLabel
     * @return bool
     */
    public function saveLabelData($orderId, $responseData, $orderData, $manualLabel = false)
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("orderId", $orderId));
        $searchOrder = $this->orderDeliveryRepository->search($criteria, $context);

        if ($searchOrder->getTotal() > 0) {
            /** @var OrderDeliveryEntity $orderDelivery */
            $orderDelivery = $searchOrder->first();
            $trackingCodes = $orderDelivery->getTrackingCodes();

            $atTrackingNumbers = [];
            $intTrackingNumbers = [];

            foreach ($responseData["importShipmentResult"] as $colloCodeList) {
                foreach ($colloCodeList["colloCodeList"] as $trackingCode) {
                    $trackingCodes[] = $trackingCode["code"];
                    if (is_null($trackingCode["ouCarrierThirdPartyID"]) || $trackingCode["ouCarrierThirdPartyID"] === "OEPAG-DEF") {
                        $atTrackingNumbers[] = $trackingCode["code"];
                    } else {
                        $intTrackingNumbers[] = $trackingCode["code"];
                    }
                }
            }

            if ($orderData["documentType"] !== "RETURN_LABEL") {
                try {
                    $this->orderDeliveryRepository->update([
                        [
                            "id" => $orderDelivery->getId(),
                            "trackingCodes" => array_unique($trackingCodes)
                        ]
                    ], $context);
                } catch (Exception $e) {
                    return false;
                }
            }

            if ($orderData["generateLabel"]) {
                try {
                    $this->plcOrderLabelsRepository->upsert([
                        [
                            "id" => Uuid::randomHex(),
                            "orderId" => $orderId,
                            "documentId" => (string)$responseData["documentId"],
                            "name" => $orderData["documentType"],
                            "atTrackingNumber" => json_encode($atTrackingNumbers, JSON_THROW_ON_ERROR),
                            "intTrackingNumber" => json_encode($intTrackingNumbers, JSON_THROW_ON_ERROR),
                            "shippingDocuments" => isset($responseData["shipmentDocuments"])
                        ]
                    ], $context);
                } catch (Exception $e) {
                    return false;
                }
            }

            if (!$manualLabel) {
                try {
                    $this->orderRepository->upsert([
                        [
                            "id" => $orderId,
                            "customFields" => [
                                "plc_automatic_label" => true
                            ]
                        ]
                    ], $context);
                } catch (Exception $e) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param OrderLabelsEntity $label
     * @param OrderEntity $order
     * @return bool
     */
    public function removeShippingLabel(OrderLabelsEntity $label, OrderEntity $order): bool
    {
        $context = Context::createDefaultContext();

        /** @var OrderDeliveryEntity $delivery */
        $delivery = $order->getDeliveries()->first();

        $currentTrackingCodes = $delivery->getTrackingCodes();
        $newTrackingCodes = [];

        foreach ($currentTrackingCodes as $code) {
            if ($code !== $label->getAtTrackingNumber() && $code !== $label->getIntTrackingNumber()) {
                $newTrackingCodes[] = $code;
            }
        }

        if ($currentTrackingCodes !== $newTrackingCodes) {
            try {
                $this->orderDeliveryRepository->update([
                    [
                        "id" => $delivery->getId(),
                        "trackingCodes" => $newTrackingCodes
                    ]
                ], $context);
            } catch (Exception $e) {
                return false;
            }
        }

        try {
            $this->plcOrderLabelsRepository->delete([["id" => $label->getId()]], $context);
        } catch (Exception $e) {
            return false;
        }

        try {
            $this->orderRepository->upsert([
                [
                    "id" => $order->getId(),
                    "customFields" => [
                        "plc_automatic_label" => false
                    ]
                ]
            ], $context);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param $orderId
     * @param $returnData
     * @param $documentId
     * @return bool
     */
    public function upsertReturnOrderData($orderId, $returnData, $documentId): bool
    {
        /** @var OrderEntity $order */
        $order = $this->getOrderData($orderId);
        if (!$order) {
            return false;
        }

        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("orderId", $orderId));

        /** @var OrderReturnDataCollection $searchOrderReturn */
        $searchOrderReturn = $this->plcOrderReturnDataRepository->search($criteria, $context);

        $id = Uuid::randomHex();
        $currentLineItems = [];
        if ($searchOrderReturn->getTotal() > 0) {
            $id = $searchOrderReturn->first()->getId();
            $currentLineItems = json_decode($searchOrderReturn->first()->getLineItems(), true);
        }

        $lineItemIds = array_keys($returnData["lineItems"]);
        $lineItems = [];
        /** @var LineItem $orderLineItem */
        foreach ($order->getLineItems() as $orderLineItem) {
            $search = array_search($orderLineItem->getId(), array_column($currentLineItems, 'id'), true);

            if (in_array($orderLineItem->getId(), $lineItemIds, true)) {
                $quantity = $returnData["lineItems"][$orderLineItem->getId()]["lineItemQuantity"];
                if ($search !== false) {
                    $quantity += $currentLineItems[$search]["quantity"];
                }

                $orderLineItem->setQuantity(min($orderLineItem->getQuantity(), $quantity));
                $lineItems[] = $orderLineItem;
            } else if ($search !== false) {
                $orderLineItem->setQuantity(min($orderLineItem->getQuantity(), $currentLineItems[$search]["quantity"]));
                $lineItems[] = $orderLineItem;
            }
        }

        if (!empty($lineItems)) {
            try {
                $this->plcOrderReturnDataRepository->upsert([
                    [
                        "id" => $id,
                        "lineItems" => json_encode($lineItems, JSON_THROW_ON_ERROR),
                        "orderId" => $orderId,
                        "returnNote" => $returnData["returnNote"],
                        "returnReasonId" => $returnData["returnReason"],
                        "documentId" => (string)$documentId
                    ]
                ], $context);

                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    public function getLabelData($documentIds)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter("documentId", $documentIds));

        $searchLabels = $this->plcOrderLabelsRepository->search($criteria, Context::createDefaultContext());
        if ($searchLabels->getTotal()) {
            $searchTypes = array_map(static function ($label) {
                return $label->getName();
            }, $searchLabels->getElements());

            return array_values(array_unique($searchTypes));
        }

        return false;
    }

    /**
     * @param $labelId
     * @return false|mixed|null
     */
    public function getLabelDataById($labelId): mixed
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("id", $labelId));

        $searchLabel = $this->plcOrderLabelsRepository->search($criteria, $context);
        if ($searchLabel->getTotal() === 0) {
            return false;
        }

        /** @var OrderEntity $order */
        return $searchLabel->first();
    }


}
