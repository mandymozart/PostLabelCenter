<?php

namespace PostLabelCenter\Controller\Storefront;

use Exception;
use PostLabelCenter\Services\GreenFieldService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class WunschfilialeController extends StorefrontController
{
    private GreenFieldService $greenFieldService;
    private EntityRepository $customerAddressRepository;
    private EntityRepository $customerRepository;
    private EntityRepository $countryRepository;
    private KernelInterface $appKernel;

    public function __construct(
        GreenFieldService $greenFieldService,
        EntityRepository  $customerAddressRepository,
        EntityRepository  $customerRepository,
        EntityRepository  $countryRepository,
        KernelInterface   $appKernel
    )
    {
        $this->greenFieldService = $greenFieldService;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->customerRepository = $customerRepository;
        $this->countryRepository = $countryRepository;
        $this->appKernel = $appKernel;
    }

    #[Route(
        path: '/wunschfiliale/search/{zipcode}/{branchKey}',
        name: 'frontend.plc.wunschfiliale.search',
        methods: ['GET'],
        defaults: ["csrf_protected" => false, "XmlHttpRequest" => true]
    )]
    public function searchAction(Request $request, Context $context): JsonResponse
    {
        if (!preg_match('/^\d{4}$/', $request->get("zipcode"))) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $this->trans("plc.wunschfiliale.zipCodeError")
            ], 500);
        }

        $cachingDirectory = $this->appKernel->getProjectDir() . "/var/cache/wunschfiliale";
        if (!file_exists($cachingDirectory)) {
            mkdir($cachingDirectory, 0755, true);
        }

        try {
            $cachingFilePath = $cachingDirectory . "/cache_" . $request->get("zipcode") . ".json";
            if (file_exists($cachingFilePath) && filectime($cachingFilePath) < time() - 3 * 24 * 60 * 60) {
                unlink($cachingFilePath);
            }

            if (!file_exists($cachingFilePath)) {
                $salesChannelId = ($request->attributes->has("sw-sales-channel-id")) ? $request->attributes->get("sw-sales-channel-id") : null;

                $response = $this->greenFieldService->getActiveBranches($request->get("zipcode"), $salesChannelId);
                file_put_contents($cachingFilePath, json_encode($response, JSON_THROW_ON_ERROR));
            } else {
                $response = json_decode(file_get_contents($cachingFilePath), true, 512, JSON_THROW_ON_ERROR);
            }

            $branchKey = $request->get("branchKey") ?? "ALL";

            if ($branchKey !== "ALL") {
                $branchTypes = self::BRANCH_TYPES;
                $filteredData = array_filter($response["data"], static function ($branch) use ($branchKey, $branchTypes) {
                    return isset($branchTypes[$branch["type"]]) && $branchKey === $branchTypes[$branch["type"]];
                });
            }

            return new JsonResponse([
                'status' => 'success',
                'data' => $branchKey === "ALL" ? $response["data"] : array_values($filteredData)
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $this->trans("plc.wunschfiliale.noBranchesFound")
            ], 500);
        }
    }

    #[Route(
        path: '/wunschfiliale/pickupStation',
        name: 'frontend.plc.wunschfiliale.pickupStation',
        methods: ['POST'],
        defaults: ["csrf_protected" => false, "XmlHttpRequest" => true]
    )]
    public function pickupStationAction(Request $request, Context $context): JsonResponse
    {
        $flashBag = $request->getSession()->getBag("flashes");

        if ($request->getContent() === "") {
            $flashBag->set("danger", [$this->trans("plc.wunschfiliale.noDataProvided")]);

            return new JsonResponse([
                'status' => 'error',
            ], 500);
        }

        $dataBag = json_decode($request->getContent(), true);

        if (!isset($dataBag['customerId']) || $dataBag["customerId"] === "") {
            $flashBag->set("danger", [$this->trans("plc.wunschfiliale.noCustomerDataFound")]);

            return new JsonResponse([
                'status' => 'error',
            ], 500);
        }

        if (empty($dataBag["branchData"])) {
            $flashBag->set("danger", [$this->trans("plc.wunschfiliale.noBranchDataFound")]);

            return new JsonResponse([
                'status' => 'error',
            ], 500);
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("customerId", $dataBag["customerId"]));
        $criteria->addFilter(new EqualsFilter("customFields.postOfficeBranchKey", $dataBag["branchData"]["branchKey"]));
        $addressSearch = $this->customerAddressRepository->search($criteria, $context);

        $criteria = new Criteria();
        $criteria->addAssociation("addresses");
        $criteria->addAssociation("defaultBillingAddress");
        $criteria->addAssociation("activeShippingAddress");
        $criteria->addFilter(new EqualsFilter("id", $dataBag["customerId"]));
        $customerSearch = $this->customerRepository->search($criteria, $context);

        if ($customerSearch->getTotal() === 0) {
            $flashBag->set("danger", [$this->trans("plc.wunschfiliale.noCustomerDataFound")]);

            return new JsonResponse([
                'status' => 'error',
            ], 500);
        }

        /** @var CustomerEntity $customer */
        $customer = $customerSearch->first();

        $address = [
            'id' => ($addressSearch->getTotal() > 0) ? $addressSearch->first()->getId() : Uuid::randomHex(),
            'customerId' => $dataBag["customerId"],
            "company" => $dataBag["branchData"]["firstLineOfAddress"],
            "firstName" => $customer->getFirstName(),
            "lastName" => $customer->getLastName(),
            "street" => $dataBag["branchData"]["address"]["streetName"] . " " . $dataBag["branchData"]["address"]["streetNumber"],
            "zipcode" => $dataBag["branchData"]["address"]["postalCode"],
            "city" => $dataBag["branchData"]["address"]["city"],
            "countryId" => $this->getCountryId($dataBag["branchData"]["address"]["country"], $context),
            "salutationId" => $customer->getSalutationId(),
            "phoneNumber" => $customer->getDefaultBillingAddress() ? $customer->getDefaultBillingAddress()->getPhoneNumber() : 'plc.wunschfiliale.phoneNumber',
            "customFields" => [
                "postOfficeBranchKey" => $dataBag["branchData"]["branchKey"],
                "postOfficeBranchType" => $dataBag["branchData"]["type"] ?? null
            ]
        ];

        try {
            $this->customerAddressRepository->upsert([$address], $context);
            $this->customerRepository->upsert([[
                "id" => $customer->getId(),
                "defaultShippingAddressId" => $address["id"]
            ]], $context);
        } catch (Exception $e) {
            $flashBag->set("danger", [$this->trans("plc.wunschfiliale.errorCreatingAddress")]);
            return new JsonResponse([
                'status' => 'error'
            ], 500);
        }

        $flashBag->add("info", $this->trans("plc.wunschfiliale.successMessage"));

        return new JsonResponse([
            'status' => 'success',
        ], 200);
    }

    public function getCountryId($iso, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $iso));
        $country = $this->countryRepository->search($criteria, $context)->first();

        if (!is_null($country)) {
            return $country->getId();
        }

        return null;
    }

    private const BRANCH_TYPES = [
        "PostOffice" => "052",
        "HPS" => "052",
        "ParcelPoint" => "052",
        "ParcelLocker" => "053"
    ];
}
