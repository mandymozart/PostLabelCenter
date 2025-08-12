<?php declare(strict_types=1);

namespace PostLabelCenter\Subscriber;

use PostLabelCenter\Core\Content\ShippingServices\ShippingServicesEntity;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\Error\GenericCartError;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Order\AccountOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\Overview\AccountOverviewPageLoadedEvent;
use Shopware\Storefront\Page\Address\Listing\AddressListingPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StorefrontSubscriber implements EventSubscriberInterface
{
    private EntityRepository $productRepository;
    private EntityRepository $plcShippingServicesRepository;
    private EntityRepository $plcReturnReasonsRepository;
    private SystemConfigService $systemConfigService;
    private TranslatorInterface $translator;

    public function __construct(EntityRepository    $productRepository,
                                EntityRepository    $plcShippingServicesRepository,
                                EntityRepository    $plcReturnReasonsRepository,
                                SystemConfigService $systemConfigService,
                                TranslatorInterface $translator)
    {
        $this->productRepository = $productRepository;
        $this->plcShippingServicesRepository = $plcShippingServicesRepository;
        $this->plcReturnReasonsRepository = $plcReturnReasonsRepository;
        $this->systemConfigService = $systemConfigService;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents()
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'plcShippingServicesEvent',
            CheckoutConfirmPageLoadedEvent::class => 'plcShippingServicesEvent',
            AccountOrderPageLoadedEvent::class => 'plcAccountOrderPageEvent',
            AccountOverviewPageLoadedEvent::class => 'plcAccountOrderPageEvent',
            OffcanvasCartPageLoadedEvent::class => 'plcShippingOffcanvasEvent',
            AddressListingPageLoadedEvent::class => 'addressListingEvent',
        ];
    }

    public function plcShippingOffcanvasEvent($event)
    {
        $this->checkCustomShippingMethods($event);
    }

    public function plcAccountOrderPageEvent($event)
    {
        $returnReasons = [];
        $pluginConfig = $this->systemConfigService->get('PostLabelCenter.config', $event->getSalesChannelContext()->getSalesChannelId());

        if ($pluginConfig && isset($pluginConfig["returnReasons"])) {
            $returnReasonIds = $pluginConfig["returnReasons"];
            if (!empty($returnReasonIds)) {
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsAnyFilter("id", $returnReasonIds));

                $searchReturnReasons = $this->plcReturnReasonsRepository->search($criteria, $event->getContext());
                if ($searchReturnReasons->getTotal() > 0) {
                    $returnReasons = $searchReturnReasons->getElements();
                }
            }
        }

        $event->getPage()->assign(["returnReasons" => $returnReasons]);
    }

    private function checkWunschfiliale($event)
    {
        /** @var Delivery $deliveries */
        $deliveries = $event->getPage()->getCart()->getDeliveries()->first();
        $wunschfilialeAvailable = false;
        $thirdPartyId = false;

        if ($deliveries) {
            $activeShippingMethod = $deliveries->getShippingMethod();

            if (isset($activeShippingMethod->getCustomFields()["plc_shipping_service"])) {
                $featureData = $this->getShippingService($activeShippingMethod->getCustomFields()["plc_shipping_service"], $event->getContext());

                $wunschfilialeAvailable = $featureData["active"];
                $thirdPartyId = $featureData["thirdPartyID"];
            }
        }

        $delivery = $event->getPage()->getCart()->getDeliveries()->first();

        if (!is_null($delivery)) {
            $address = $delivery->getLocation()->getAddress();
            if ($address !== null) {
                $addressCustomFields = $address->getCustomFields();

                if ($thirdPartyId && !is_null($addressCustomFields) && isset($addressCustomFields["postOfficeBranchType"], self::ACCEPTED_BRANCH_TYPES[$thirdPartyId])
                    && !in_array($addressCustomFields["postOfficeBranchType"], self::ACCEPTED_BRANCH_TYPES[$thirdPartyId], true)) {
                    $flashBag = $event->getRequest()->getSession()->getBag("flashes");
                    $flashBag->set("danger", [$this->translator->trans("plc.error.wrongBranchKey")]);

                    $error = new ErrorCollection();
                    $error->add(new GenericCartError("addressWrongBranch", "Wrong branch for address", [], 100, true, false, true));
                    $event->getPage()->getCart()->setErrors($error);
                } else if (!$thirdPartyId && !is_null($addressCustomFields) && isset($addressCustomFields["postOfficeBranchType"]) && $address->getCustomFields()["postOfficeBranchType"]) {
                    $flashBag = $event->getRequest()->getSession()->getBag("flashes");
                    $flashBag->set("danger", [$this->translator->trans("plc.error.setNoWunschfiliale")]);

                    $error = new ErrorCollection();
                    $error->add(new GenericCartError("addressNoWunschfiliale", "No wunschfiliale available", [], 100, true, false, true));
                    $event->getPage()->getCart()->setErrors($error);
                } else if ($thirdPartyId && (is_null($addressCustomFields) || !isset($addressCustomFields["postOfficeBranchType"]))) {
                    $flashBag = $event->getRequest()->getSession()->getBag("flashes");
                    $flashBag->set("danger", [$this->translator->trans("plc.error.setAddressNoWunschfiliale")]);

                    $error = new ErrorCollection();
                    $error->add(new GenericCartError("addressNoWunschfiliale", "No wunschfiliale address set", [], 100, true, false, true));
                    $event->getPage()->getCart()->setErrors($error);
                }
            }
        }

        return [
            "wunschfilialeAvailable" => $wunschfilialeAvailable,
            "thirdPartyId" => $thirdPartyId
        ];
    }

    /**
     * @throws \JsonException
     */
    public function plcShippingServicesEvent($event)
    {
        $wunschFilialeData = $this->checkWunschfiliale($event);
        $customShippingOnly = $this->checkCustomShippingMethods($event);

        $event->getPage()->assign(["wunschfilialeAvailable" => $customShippingOnly === true ? false : $wunschFilialeData["wunschfilialeAvailable"], "thirdPartyID" => $wunschFilialeData["thirdPartyId"]]);
    }

    private function checkCustomShippingMethods($event)
    {
        $lineItems = $event->getPage()->getCart()->getLineItems();
        $dangerousGoods = false;
        $fragile = false;
        if (!empty($lineItems)) {

            /** @var LineItem $lineItem */
            foreach ($lineItems as $lineItem) {
                if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                    continue;
                }

                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter("productNumber", $lineItem->getPayloadValue("productNumber")));
                $search = $this->productRepository->search($criteria, $event->getContext());

                $customFields = [];
                if ($search->getTotal() > 0) {
                    $product = $search->first();
                    $customFields = $product->getCustomFields();
                }

                if (isset($customFields["plc_fragile"]) && $customFields["plc_fragile"] === true) {
                    $fragile = true;
                }

                if (isset($customFields["plc_dangerousGoods"]) && $customFields["plc_dangerousGoods"] === true) {
                    $dangerousGoods = true;
                }
            }

            if ($dangerousGoods || $fragile) {
                $shippingMethods = $event->getPage()->getShippingMethods();

                $newShippingMethods = $this->filterShippingMethods($shippingMethods, $dangerousGoods, $fragile);

                $event->getPage()->setShippingMethods($newShippingMethods);
                $event->getPage()->getCart()->setDeliveries(new DeliveryCollection());

                if (count($newShippingMethods->getElements()) === 0) {
                    $flashBag = $event->getRequest()->getSession()->getBag("flashes");
                    $flashBag->set("danger", [$this->translator->trans("plc.error.noShippingMethods")]);

                    $error = new ErrorCollection();
                    $error->add(new GenericCartError("noShippingMethods", "noShippingMethodAvailable", [], 1, true, false, true));
                    $event->getPage()->getCart()->setErrors($error);
                }
            }
        }

        return ($dangerousGoods || $fragile);
    }

    private function filterShippingMethods($shippingMethods, $dangerousGoods, $fragile): ShippingMethodCollection
    {
        $newShippingMethods = new ShippingMethodCollection();
        $context = Context::createDefaultContext();

        foreach ($shippingMethods as $shippingMethod) {
            if (!isset($shippingMethod->getCustomFields()["plc_shipping_service"])) {
                continue;
            }

            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter("id", $shippingMethod->getCustomFields()["plc_shipping_service"]));

            $searchShippingMethod = $this->plcShippingServicesRepository->search($criteria, $context);

            if ($searchShippingMethod->getTotal() === 0) {
                continue;
            }

            /** @var ShippingServicesEntity $plcShippingMethod */
            $plcShippingMethod = $searchShippingMethod->first();

            $featureList = json_decode($plcShippingMethod->getFeatureList(), true, 512, JSON_THROW_ON_ERROR);
            if (empty($featureList)) {
                continue;
            }

            foreach ($featureList as $feature) {
                if ($fragile && !$dangerousGoods && in_array($feature["thirdPartyID"], self::FRAGILE, true)) {
                    $newShippingMethods->add($shippingMethod);
                    break;
                }

                if ($dangerousGoods && !$fragile && in_array($feature["thirdPartyID"], self::DANGEROUS_GOODS, true)) {
                    $newShippingMethods->add($shippingMethod);
                    break;
                }

                if ($fragile && $dangerousGoods && in_array($feature["thirdPartyID"], self::FRAGILE, true)
                    && in_array($feature["thirdPartyID"], self::DANGEROUS_GOODS, true)) {
                    $newShippingMethods->add($shippingMethod);
                    break;
                }
            }
        }
        return $newShippingMethods;
    }

    private function getShippingService($serviceId, $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("id", $serviceId));
        $searchService = $this->plcShippingServicesRepository->search($criteria, $context);

        if ($searchService->getTotal() === 0) {
            return ["active" => false, "thirdPartyID" => false];
        }

        /** @var ShippingServicesEntity $shippingService */
        $shippingService = $searchService->first();

        $features = json_decode($shippingService->getFeatureList(), true, 512, JSON_THROW_ON_ERROR);

        if (empty($features)) {
            return ["active" => false, "thirdPartyID" => false];
        }

        foreach ($features as $feature) {
            if (in_array($feature["thirdPartyID"], self::ACCEPTED_FEATURES, true)) {
                return ["active" => true, "thirdPartyID" => $feature["thirdPartyID"]];
            }
        }

        return ["active" => false, "thirdPartyID" => false];
    }

    public function addressListingEvent(AddressListingPageLoadedEvent $event)
    {
        $defaultBillingAddress = $event->getSalesChannelContext()->getCustomer()->getDefaultShippingAddress();
        if (!is_null($defaultBillingAddress) && !is_null($defaultBillingAddress->getCustomFields())
            && isset($defaultBillingAddress->getCustomFields()["postOfficeBranchKey"]) && $defaultBillingAddress->getCustomFields()["postOfficeBranchKey"]) {
            $event->getSalesChannelContext()->getCustomer()->setDefaultShippingAddress($event->getSalesChannelContext()->getCustomer()->getActiveBillingAddress());
            $event->getSalesChannelContext()->getCustomer()->setActiveShippingAddress($event->getSalesChannelContext()->getCustomer()->getActiveBillingAddress());
            $event->getSalesChannelContext()->getCustomer()->setDefaultShippingAddressId($event->getSalesChannelContext()->getCustomer()->getActiveBillingAddress()->getId());
        }
    }

    private const ACCEPTED_FEATURES = ["052", "053"];
    private const ACCEPTED_BRANCH_TYPES = [
        "052" => [
            "PostOffice", "HPS", "ParcelPoint"
        ],
        "053" => ["ParcelLocker"]
    ];

    private const FRAGILE = [
        "004", "024"
    ];

    private const DANGEROUS_GOODS = [
        "074"
    ];
}
