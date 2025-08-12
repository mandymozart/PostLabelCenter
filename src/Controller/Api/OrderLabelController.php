<?php

namespace PostLabelCenter\Controller\Api;

use Exception;
use PostLabelCenter\Services\GreenFieldService;
use PostLabelCenter\Services\OrderShippingHelper;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;


#[Route(defaults: ['_routeScope' => ['api']])]
class OrderLabelController extends AbstractController
{
    private GreenFieldService $greenFieldService;
    private OrderShippingHelper $orderShippingHelper;
    private EntityRepository $dailyStatementRepository;

    public function __construct(GreenFieldService $greenFieldService, OrderShippingHelper $orderShippingHelper, EntityRepository $dailyStatementRepository)
    {
        $this->greenFieldService = $greenFieldService;
        $this->orderShippingHelper = $orderShippingHelper;
        $this->dailyStatementRepository = $dailyStatementRepository;
    }

    #[Route(
        path: '/api/plc/shipping-data',
        name: 'api.plc.shipping.data',
        methods: ['POST']
    )]
    public function getShippingData(Request $request, Context $context): JsonResponse
    {
        if ((!$request->request->has("orderId") || !$request->request->has("pdfLabelId") || !$request->request->has("documentId")) || !Uuid::isValid($request->request->get("orderId")) || !Uuid::isValid($request->request->get("pdfLabelId"))) {
            return new JsonResponse(["data" => false, "message" => "plc.order.postLabels.messages.missingValues"], 200);
        }

        /** @var OrderEntity $order */
        $order = $this->orderShippingHelper->getOrderData($request->request->get("orderId"));

        $pdfData = $this->greenFieldService->getPDFData($order->getOrderNumber(), $order->getSalesChannelId(),
            $request->request->get("documentId"), $request->request->get("shippingContent"));
        if ($pdfData) {
            $this->orderShippingHelper->saveLabelAsDownloaded($request->request->get("pdfLabelId"));

            return new JsonResponse(["data" => $pdfData, "message" => "plc.order.postLabels.messages.createLabelSuccess"], 200);
        }

        return new JsonResponse(["data" => false, "message" => "plc.order.postLabels.messages.createLabelError"], 200);
    }

    #[Route(
        path: '/api/plc/cancel-shipment',
        name: 'api.plc.cancel-shipment',
        methods: ['POST']
    )]
    public function cancelShipment(Request $request): JsonResponse
    {
        if ((!$request->request->has("orderId") || !$request->request->has("labelId")) || !Uuid::isValid($request->request->get("orderId")) || !Uuid::isValid($request->request->get("labelId"))) {
            return new JsonResponse(["data" => false, "message" => "plc.order.postLabels.messages.missingValues"], 200);
        }
        $canceledShipment = $this->greenFieldService->cancelShipment($request->request->get("orderId"), $request->request->get("labelId"));

        return new JsonResponse(["data" => $canceledShipment, "message" => "plc.order.postLabels.messages.cancelSuccess"], 200);
    }

    #[Route(
        path: '/api/plc/daily-statement',
        name: 'api.plc.daily-statement',
        methods: ['POST']
    )]
    public function dailyStatement(Request $request, Context $context): JsonResponse
    {
        if (!$request->request->has("salesChannelId") || !Uuid::isValid($request->request->get("salesChannelId"))) {
            return new JsonResponse(["data" => false, "message" => "plc.dailyStatement.messages.missingValues"], 200);
        }

        $dailyStatement = $this->greenFieldService->getDailyStatement($request->request->get("salesChannelId"), $request->request->get("statementDate"));

        if ($dailyStatement["data"]) {
            if (is_array($dailyStatement["data"])) {
                try {
                    $this->dailyStatementRepository->upsert([
                        [
                            "id" => Uuid::randomHex(),
                            "documentId" => (string)$dailyStatement["data"]["@id"],
                            "plcDateAdded" => gmdate("d-m-Y H:i:s", $dailyStatement["data"]["createdOn"] / 1000),
                            "plcCreatedOn" => gmdate("d-m-Y H:i:s", $dailyStatement["data"]["dateAdded"] / 1000),
                            "salesChannelId" => $request->request->get("salesChannelId"),
                            "pdfData" => json_encode($dailyStatement["data"]["fileContent"], JSON_THROW_ON_ERROR),
                        ]
                    ], $context);

                    return new JsonResponse(["data" => $dailyStatement["data"]["fileContent"], "message" => $dailyStatement["message"]], 200);
                } catch (Exception $e) {
                    return new JsonResponse(["data" => false, "message" => "plc.dailyStatement.messages.errorCreatingStatement"], 200);
                }
            } else {
                return new JsonResponse(["data" => true, "message" => $dailyStatement["message"]], 200);
            }
        }

        return new JsonResponse(["data" => false, "message" => $dailyStatement["message"]], 200);

    }

    #[Route(
        path: '/api/plc/create-shipment',
        name: 'api.plc.create-shipment',
        methods: ['POST']
    )]
    public function createShipment(Request $request, Context $context): JsonResponse
    {
        if (!$request->request->has("orderId") || !$request->request->has("salesChannelId") || !Uuid::isValid($request->request->get("orderId")) || !Uuid::isValid($request->request->get("salesChannelId"))) {
            return new JsonResponse(["data" => false, "message" => "plc.order.postLabels.messages.missingValues"], 200);
        }

        $createShipping = $this->greenFieldService->createShippingDocuments($request->request->get("orderId"), $request->request->get("salesChannelId"));

        return new JsonResponse(["data" => $createShipping["data"] ?? $createShipping, "message" => $createShipping["message"] ?? ""], 200);
    }

    #[Route(
        path: '/api/plc/get-country-list',
        name: 'api.plc.get-country-list',
        methods: ['POST']
    )]
    public function getCountryList(Request $request, Context $context): JsonResponse
    {
        $countryList = $this->greenFieldService->getCountryList();

        if ($countryList) {
            $adaptedCountryList = [];
            foreach ($countryList as $country) {
                $adaptedCountryList[$country["countryISO2"]] = $country["countryname"];
            }

            asort($adaptedCountryList);

            return new JsonResponse(["data" => $adaptedCountryList, "message" => null], 200);
        }

        return new JsonResponse(["data" => false, "message" => null], 200);
    }

    #[Route(
        path: '/api/plc/create-manual-shipment',
        name: 'api.plc.create-manual-shipment',
        methods: ['POST']
    )]
    public function createManualShipment(Request $request, Context $context): JsonResponse
    {
        $payload = $request->request->all();

        foreach (self::MANUAL_SHIPMENT_REQ as $fieldName) {
            if (!isset($payload[$fieldName])) {
                return new JsonResponse(["data" => false, "message" => "plc.order.postLabels.messages.missingValues"], 200);
            }
        }

        $createShipping = $this->greenFieldService->createShippingDocuments(null, $payload["salesChannelId"], $payload);
        if ($createShipping) {
            return new JsonResponse(["data" => $createShipping["data"], "message" => $createShipping["message"]], 200);
        }

        return new JsonResponse(["data" => false, "message" => "plc.order.postLabels.messages.errorCreatingLabel"], 200);
    }

    #[Route(
        path: '/api/plc/bulk-shipment',
        name: 'api.plc.bulk-shipment',
        methods: ['POST']
    )]
    public function bulkCreateShipments(Request $request): JsonResponse
    {
        if (!$request->request->has("selectedLabelType")) {
            return new JsonResponse(["data" => false, "message" => "plc.order.postLabels.messages.missingValues"], 500);
        }

        $orders = json_decode($request->request->get("orders"), true, 512, JSON_THROW_ON_ERROR);
        if (empty($orders)) {
            return new JsonResponse(["data" => false, "message" => "plc.order.postLabels.messages.missingValues"], 500);
        }

        $successOrders = json_decode($request->request->get("successOrders"), true, 512, JSON_THROW_ON_ERROR);
        $orderIds = array_map(static function ($order) {
            return $order["id"];
        }, $orders);

        unset($order);

        $bulkOrderData = json_decode($request->request->get("bulkOrderData"), true, 512, JSON_THROW_ON_ERROR);
        $overrideData = [];
        foreach ($bulkOrderData as $bulkData) {
            $overrideData[$bulkData["id"]] = $bulkData["data"];
        }

        $createMultiple = $this->greenFieldService->createMultipleShippingDocuments($orderIds, $overrideData, $request->request->get("newDeliveryState"), $request->request->get("selectedLabelType"));
        if ($createMultiple) {
            foreach ($createMultiple["successOrders"] as $successOrder) {
                $search = array_search($successOrder["orderNumber"], array_column($successOrders, 'orderNumber'), true);
                if ($search === false) {
                    $successOrders[] = $successOrder;
                }
            }

            $failedOrders = $createMultiple["failedOrders"];
        } else {
            $failedOrders = array_map(static function ($order) {
                return [
                    "orderNumber" => $order["orderNumber"],
                    "errorMessage" => "Bitte Logs oder vorherige Fehlermeldungen überprüfen"
                ];
            }, $orders);
        }

        return new JsonResponse([
            "failedOrders" => $failedOrders,
            "successOrders" => $successOrders,
            "failedTransitions" => isset($createMultiple["failedTransitions"]) && $createMultiple["failedTransitions"] !== "" ? $createMultiple["failedTransitions"] : null
        ], 200);
    }

    #[Route(
        path: '/api/plc/bulk-shipment/download',
        name: 'api.plc.bulk-shipment.download',
        methods: ['POST']
    )]
    public function bulkShipmentDownload(Request $request, Context $context): JsonResponse
    {
        $successOrders = json_decode($request->request->get("successOrders"), true, 512, JSON_THROW_ON_ERROR);
        if (empty($successOrders)) {
            return new JsonResponse(["download" => false, "fileName" => null]);
        }

        $fileName = "Labels_" . date("ymd_His") . ".zip";
        $zip = new ZipArchive();
        $zip->open($fileName, ZipArchive::CREATE);

        foreach ($successOrders as $order) {
            if (!empty($order["data"])) {
                foreach ($order["data"] as $type => $pdf) {
                    $zip->addFromString($type . "_" . $order["orderNumber"] . ".pdf", base64_decode($pdf));
                }
            }
        }

        $zip->close();
        if (file_exists($fileName)) {
            $base64Zip = base64_encode(file_get_contents($fileName));
            unlink($fileName);
        }

        return new JsonResponse(["download" => $base64Zip ?? null, "fileName" => $fileName]);
    }

    #[Route(
        path: '/api/plc/merged-label/download',
        name: 'api.plc.merged-label.download',
        methods: ['POST']
    )]
    public function mergedLabelDownload(Request $request, Context $context): JsonResponse
    {
        $orders = json_decode($request->request->get("orders"), true);

        if (empty($orders)) {
            return new JsonResponse(["download" => null, "allOrders" => null]);
        }

        $salesChannelOrders = [];

        foreach ($orders as $order) {
            $salesChannelOrders[$order["salesChannelId"]][] = $order["orderNumber"];
        }

        if (empty($salesChannelOrders)) {
            return new JsonResponse(["download" => false]);
        }

        $responseOrders = $this->greenFieldService->createMergedLabel($salesChannelOrders);

        if (empty($responseOrders)) {
            return new JsonResponse(["download" => null, "allOrders" => null]);
        }

        $fileName = "Labels_" . date("ymd_His") . ".zip";
        $zip = new ZipArchive();
        $zip->open($fileName, ZipArchive::CREATE);

        $allOrders = [];
        foreach ($responseOrders as $salesChannelId => $data) {
            $zip->addFromString($salesChannelId . ".pdf", base64_decode($data["pdfData"]));

            foreach ($data["orders"] as $order) {
                $allOrders[] = $order;
            }
        }

        $zip->close();

        $base64Zip = base64_encode(file_get_contents($fileName));
        unlink($fileName);

        return new JsonResponse(["download" => $base64Zip, "filename" => $fileName, "allOrders" => $allOrders]);
    }

    private const MANUAL_SHIPMENT_REQ = [
        "deliveryAddress",
        "returnAddress",
        "shipperAddress",
        "shippingService",
        "lineItems",
        "customsData",
        "orderData",
        "selectedLabelType",
        "bankData"
    ];
}
