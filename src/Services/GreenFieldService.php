<?php declare(strict_types=1);

namespace PostLabelCenter\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use PostLabelCenter\Core\Content\OrderLabels\OrderLabelsEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionEntity;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class GreenFieldService
{
    private ?Client $restClient = null;
    private $pluginConfig;
    private array $greenfieldMeta = [];

    public function __construct(private readonly SystemConfigService  $systemConfigService,
                                private readonly OrderShippingHelper  $orderShippingHelper,
                                private readonly EntityRepository     $loggerRepository,
                                private readonly StateMachineRegistry $stateMachineRegistry,
                                private readonly string               $shopVersion)
    {
    }

    private function init($salesChannelId = null): void
    {
        $this->pluginConfig = $this->systemConfigService->get('PostLabelCenter.config', $salesChannelId);
        $pluginMode = (isset($this->pluginConfig["enableTestMode"]) && $this->pluginConfig["enableTestMode"]) ? "test" : "live";

        if (!isset($this->pluginConfig[$pluginMode . "ClientId"], $this->pluginConfig[$pluginMode . "OrgUnitGUID"], $this->pluginConfig[$pluginMode . "OrgUnitId"])) {
            $this->createLogEntry("Missing required plugin credentials", []);
            return;
        }

        $this->restClient = new Client([
            "base_uri" => ($pluginMode === "live") ? self::LIVE_BASE_URI : self::TEST_BASE_URI,
            "auth" => [
                ($pluginMode === "live") ? self::LIVE_USER : self::TEST_USER,
                ($pluginMode === "live") ? self::LIVE_CREDENTIALS : self::TEST_CREDENTIALS
            ],
            'headers' => ['Content-type' => 'application/json']
        ]);

        $this->greenfieldMeta = [
            "clientId" => $this->pluginConfig[$pluginMode . "ClientId"],
            "orgUnitGuID" => $this->pluginConfig[$pluginMode . "OrgUnitGUID"],
            "orgUnitID" => $this->pluginConfig[$pluginMode . "OrgUnitId"],
            "shopType" => "Shopware",
            "shopVersion" => $this->shopVersion,
        ];
    }

    public function sanityCheck(): bool
    {
        $this->init();

        try {
            $request = $this->restClient->post("sanityCheck", [
                ["meta" => $this->greenfieldMeta]
            ]);

            $response = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if ($response && $response["data"][0] === "OK") {
                return true;
            }
        } catch (Exception $e) {
            $this->createLogEntry($e->getMessage(), $e->getTrace());
        }

        return false;
    }

    public function createMergedLabel($salesChannelOrders): array
    {
        $responseData = [];
        foreach ($salesChannelOrders as $salesChannelId => $orders) {
            $this->init($salesChannelId);

            if (is_null($this->restClient)) {
                continue;
            }

            try {
                $request = $this->restClient->post("shipping/pdf/merge", [
                    "json" =>
                        [
                            "orderIds" => $orders,
                            "documentTypes" => "CUSTOMS_AND_SHIPPING_OR_RETURN_DOCUMENTS",
                            "limit" => 100,
                            "clientId" => $this->greenfieldMeta["clientId"],
                            "orgUnitGuID" => $this->greenfieldMeta["orgUnitGuID"],
                            "orgUnitID" => $this->greenfieldMeta["orgUnitID"],
                            "shopId" => $salesChannelId,
                            "footer" => true
                        ]
                ]);

                $response = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

                if ($response && is_null($response["errorCode"]) && !empty($response["data"][0]["orderDocuments"])) {
                    foreach ($response["data"][0]["orderDocuments"] as $orderNumber => $orderDocument) {
                        $labelTypes = $this->orderShippingHelper->getLabelData($orderDocument);

                        $responseData[$salesChannelId]["orders"][] = [
                            "orderNumber" => $orderNumber,
                            "labelTypes" => ($labelTypes) ? implode(", ", $labelTypes) : ""
                        ];
                    }


                    if (!isset($responseData[$salesChannelId]["pdfData"]) || $responseData[$salesChannelId]["pdfData"] === "") {
                        $responseData[$salesChannelId]["pdfData"] = $response["data"][0]["base64Pdf"];
                    }
                }
            } catch (Exception $e) {
                $this->createLogEntry($e->getMessage(), $e->getTrace());
            }
        }

        return $responseData;
    }

    public function createShippingDocuments($orderId, $salesChannelId, $overrideData = false)
    {
        if (is_null($orderId)) {
            $orderId = $overrideData["orderData"]["id"];
        }

        $this->init($salesChannelId);
        if (is_null($this->restClient)) {
            return false;
        }

        if (Uuid::isValid($orderId)) {
            $orderData = $this->orderShippingHelper->mapShippingPdf($orderId, $this->pluginConfig, $overrideData);

            if ($orderData) {
                if ($orderData["documentType"] !== "BOTH") {
                    return $this->pdfRequest($orderData, $orderId, $overrideData);
                }

                $orderData["documentType"] = "SHIPPING_LABEL";
                $shippingLabel = $this->pdfRequest($orderData, $orderId, $overrideData);

                if (!$shippingLabel["data"]) {
                    return $shippingLabel;
                }

                $orderData["documentType"] = "RETURN_LABEL";
                return $this->pdfRequest($orderData, $orderId, $overrideData);
            }
        }

        return false;
    }

    public function createMultipleShippingDocuments($orders, $overrideData, $newDeliveryState, $documentType)
    {
        $this->init();
        if (is_null($this->restClient)) {
            return false;
        }

        $generateLabel = !(isset($this->pluginConfig["onlyDataimport"]) && $this->pluginConfig["onlyDataimport"]);

        $bulkOrders = [];
        foreach ($orders as $orderId) {
            if (Uuid::isValid($orderId)) {
                $orderData = $this->orderShippingHelper->mapShippingPdf($orderId, $this->pluginConfig, $overrideData[$orderId] ?? false);

                if ($orderData) {
                    $orderData["documentType"] = strtoupper($documentType);
                    if ($orderData["documentType"] === "BOTH") {
                        $orderData["documentType"] = "SHIPPING_LABEL";
                        $bulkOrders[] = $orderData;

                        $orderData["documentType"] = "RETURN_LABEL";
                    }

                    $bulkOrders[] = $orderData;
                }
            }
        }

        if (!empty($bulkOrders)) {
            $successOrders = [];
            $failedOrders = [];
            $failedTransitions = [];

            try {
                $request = $this->restClient->post("shipping/pdf/generateMultiple", [
                    "json" => [
                        "batch" => [
                            [
                                "data" => $bulkOrders,
                                "meta" => $this->greenfieldMeta,
                            ]
                        ],
                        "attachedDocuments" => $generateLabel
                    ]
                ]);

                $response = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

                if (!empty($response["data"][0]["multiplePdfResponses"])) {
                    foreach ($response["data"][0]["multiplePdfResponses"] as $multiplePdfResponse) {
                        $generatedPdf = $multiplePdfResponse["generatedPdfs"];
                        $orderNumber = (string)array_key_first($generatedPdf);
                        $pdfData = $generatedPdf[$orderNumber];

                        /** @var OrderEntity $orderData */
                        $orderData = $this->orderShippingHelper->getOrderDataByNumber($orderNumber);

                        if ($orderData) {
                            $dataSaved = $this->orderShippingHelper->saveLabelData($orderData->getId(), $pdfData,
                                ["documentType" => $pdfData["documentType"], "generateLabel" => $generateLabel], true);
                        }

                        if (isset($dataSaved)) {
                            $data = (isset($successOrders[$orderNumber])) ? $successOrders[$orderNumber]["data"] : [];
                            $data[$pdfData["documentType"]] = $pdfData["pdfData"];

                            $successOrders[$orderData->getId()] = [
                                "id" => $orderData->getId(),
                                "orderNumber" => $orderNumber,
                                "data" => array_filter($data)
                            ];

                            if (!is_null($newDeliveryState) && Uuid::isValid($newDeliveryState)) {
                                $transitioned = false;
                                $delivery = $orderData->getDeliveries()?->first();

                                if ($delivery) {
                                    $transitioned = $this->transitionDeliveryState($delivery->getId(), $newDeliveryState, $delivery->getStateMachineState()->getId());
                                }

                                if (!$delivery || !$transitioned) {
                                    $failedTransitions[] = $orderData->getOrderNumber();
                                }
                            }
                        } else {
                            $failedOrders[] = $orderData->getId();
                        }
                    }
                }

                if (!empty($response["data"][0]["errorResponseList"])) {
                    foreach ($response["data"][0]["errorResponseList"] as $error) {
                        $failedOrders[] = [
                            "orderNumber" => (string)$error["orderId"],
                            "errorMessage" => $error["errorText"]
                        ];
                    }
                }

                return ["successOrders" => $successOrders, "failedOrders" => $failedOrders, "failedTransitions" => implode(", ", array_unique($failedTransitions))];
            } catch (ServerException|ClientException $e) {
                $this->createLogEntry($e->getMessage(), [
                    "message" => $e->getResponse()->getBody()->getContents(),
                    "trace" => $e->getTrace()
                ]);
            } catch (Exception $e) {
                $this->createLogEntry($e->getMessage(), $e->getTrace());
            }

            return false;
        }

        return false;
    }

    private function pdfRequest($orderData, $orderId, $returnData = false)
    {
        try {
            $request = $this->restClient->post("shipping/pdf/generate", [
                "json" => [
                    "data" => $orderData,
                    "meta" => $this->greenfieldMeta
                ]
            ]);

            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)["data"];

            $this->orderShippingHelper->saveLabelData($orderId, $data[0],
                $orderData);

            return [
                "data" => ($returnData) ? $data : true,
                "message" => null
            ];
        } catch (ServerException|ClientException|RequestException $e) {
            $message = json_decode($e->getResponse()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)["errorDescription"];
            $this->createLogEntry($message, $e->getTrace());
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->createLogEntry($e->getMessage(), $e->getTrace());
        }

        return [
            "data" => false,
            "message" => $message
        ];
    }

    public function createFrontendReturn($orderId, $salesChannelId, $returnData)
    {
        $this->init($salesChannelId);
        if (is_null($this->restClient)) {
            return false;
        }

        if (Uuid::isValid($orderId)) {
            $orderData = $this->orderShippingHelper->mapShippingPdf($orderId, $this->pluginConfig);

            if ($orderData) {
                $orderData["documentType"] = "RETURN_LABEL";

                $pdfData = $this->pdfRequest($orderData, $orderId, true);

                if ($pdfData["data"]) {
                    $saveReturnData = $this->orderShippingHelper->upsertReturnOrderData($orderId, $returnData, $pdfData["data"][0]["documentId"]);

                    if ($saveReturnData) {
                        return $pdfData;
                    }
                }
            }
        }

        return false;
    }

    public function getShippingServices($salesChannelId, $isoCodes)
    {
        $this->init($salesChannelId);
        if (is_null($this->restClient)) {
            return false;
        }

        try {
            $request = $this->restClient->post("deliveryOptions/services/byCountry", [
                "json" => [
                    "data" => $isoCodes,
                    "meta" => $this->greenfieldMeta
                ]
            ]);

            if ($request->getStatusCode() === 200) {
                return json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)["data"];
            }
        } catch (ServerException|ClientException $e) {
            $this->createLogEntry($e->getResponse()->getBody()->getContents(), $e->getTrace());
        } catch (Exception $e) {
            $this->createLogEntry($e->getMessage(), $e->getTrace());
        }

        return false;
    }

    public function checkFeatureList($featureList)
    {
        $this->init();
        if (is_null($this->restClient)) {
            return false;
        }

        try {
            $request = $this->restClient->post("deliveryOptions/features/checkCombination", [
                "json" => [
                    "data" => $featureList,
                    "meta" => $this->greenfieldMeta
                ]
            ]);

            if ($request->getStatusCode() === 200) {
                return json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)["data"];
            }
        } catch (Exception $e) {
            $this->createLogEntry($e->getMessage(), $e->getTrace());
        }

        return false;
    }

    public function getPDFData($orderNumber, $salesChannelId, $documentId, $shippingDocuments = false)
    {
        $this->init($salesChannelId);
        if (is_null($this->restClient)) {
            return false;
        }

        try {
            $request = $this->restClient->post("shipping/pdf/load", [
                "json" => [
                    "data" => [
                        "orderId" => $orderNumber,
                        "shopId" => $salesChannelId
                    ],
                    "meta" => $this->greenfieldMeta
                ]
            ]);

            if ($request->getStatusCode() === 200) {
                $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)["data"];

                foreach ($data as $pdf) {
                    if ($pdf["id"] === (int)$documentId) {
                        return ($shippingDocuments) ? $pdf["shippingContent"] : $pdf["fileContent"];
                    }
                }
            }
        } catch (ServerException|ClientException $e) {
            $this->createLogEntry(json_decode($e->getResponse()->getBody()->getContents()), $e->getTrace());
        } catch (Exception $e) {
            $this->createLogEntry($e->getMessage(), $e->getTrace());
        }

        return false;
    }

    public function cancelShipment($orderId, $labelId)
    {
        /** @var OrderEntity $order */
        $order = $this->orderShippingHelper->getOrderData($orderId);

        /** @var OrderLabelsEntity $label */
        $label = $this->orderShippingHelper->getLabelDataById($labelId);

        if (!$order || !$label) {
            return false;
        }

        $this->init($order->getSalesChannelId());
        if (is_null($this->restClient)) {
            return false;
        }

        try {
            $request = $this->restClient->post("shipping/cancelShipment", [
                'json' => [
                    "data" => [
                        "documentIds" => [$label->getDocumentId()],
                        'orderId' => $order->getOrderNumber(),
                        'shopId' => $order->getSalesChannelId()
                    ],
                    "meta" => $this->greenfieldMeta
                ]
            ]);

            if ($request->getStatusCode() === 200) {
                $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)["data"];

                if (isset($data[0]["invalidCanceledShipment"])
                    && ($data[0]["invalidCanceledShipment"][0]["errorCode"] !== "SN#10020"
                        && $data[0]["invalidCanceledShipment"][0]["cancelSuccessful"] === false)) {
                    return false;
                }

                if ((isset($data[0]["validCanceledShipment"]) && $data[0]["validCanceledShipment"][0]["cancelSuccessful"] === true) ||
                    (isset($data[0]["invalidCanceledShipment"]) && $data[0]["invalidCanceledShipment"][0]["errorCode"] === "SN#10020")) {
                    $this->orderShippingHelper->removeShippingLabel($label, $order);
                }

                return true;
            }
        } catch (ServerException|ClientException $e) {
            $this->createLogEntry(json_decode($e->getResponse()->getBody()->getContents()), $e->getTrace());
        } catch (Exception $e) {
            $this->createLogEntry($e->getMessage(), $e->getTrace());
        }

        return false;
    }

    public function getDailyStatement($salesChannelId, $date = null)
    {
        $this->init($salesChannelId);
        if (is_null($this->restClient)) {
            return false;
        }

        try {
            if (is_null($date)) {
                $request = $this->restClient->post("shipping/performEndOfDay", [
                    'headers' => [
                        'Content-type' => 'application/json',
                        "Accept" => "application/pdf"
                    ],
                    'json' => [
                        "meta" => $this->greenfieldMeta,
                    ]
                ]);
            } else {
                $request = $this->restClient->post("shipping/performEndOfDay/search", [
                    'json' => [
                        "meta" => $this->greenfieldMeta,
                        "data" => [
                            "date" => date("Y-m-d", strtotime($date))
                        ]
                    ]
                ]);
            }

            $response = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if ($response && !empty($response["data"])) {
                return [
                    "data" => $response["data"][0],
                    "message" => "plc.dailyStatement.messages.successCreating"
                ];
            }
        } catch (ServerException|Exception $e) {
            $this->createLogEntry($e->getMessage(), $e->getTrace());

            return [
                "data" => false,
                "message" => "plc.dailyStatement.messages.errorCreatingStatement",
            ];
        }

        return [
            "data" => true,
            "message" => "plc.dailyStatement.messages.successNoData"
        ];
    }

    private function transitionDeliveryState($deliveryId, $newStateId, $currentStateId)
    {
        if ($newStateId === $currentStateId) {
            return true;
        }

        try {
            $context = Context::createDefaultContext();

            $transitions = $this->stateMachineRegistry->getAvailableTransitions(
                OrderDeliveryDefinition::ENTITY_NAME,
                $deliveryId,
                'stateId',
                $context
            );

            /** @var StateMachineTransitionEntity $transition */
            foreach ($transitions as $transition) {
                if ($transition->getToStateMachineState()->getId() === $newStateId) {
                    $context->assign([
                        'greenFieldApi' => true,
                    ]);

                    $this->stateMachineRegistry->transition(new Transition(
                        OrderDeliveryDefinition::ENTITY_NAME,
                        $deliveryId,
                        $transition->getActionName(),
                        'stateId'
                    ), $context);

                    return true;
                }
            }
        } catch (IllegalTransitionException|Exception $e) {
            $this->createLogEntry($e->getMessage(), $e->getTrace());
        }

        return false;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function getActiveBranches($zipcode, $salesChannelId)
    {
        $this->init($salesChannelId);
        if (is_null($this->restClient)) {
            return false;
        }

        $response = $this->restClient->post("branch/getActiveBranches", [
            "json" => [
                "data" => [
                    "country" => "AT",
                    "distance" => 5000,
                    "postalCode" => $zipcode,
                    "type" => "ALL"
                ],
                "meta" => $this->greenfieldMeta
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        }

        return false;
    }

    public function getCountryList()
    {
        $this->init();
        if (is_null($this->restClient)) {
            return false;
        }

        try {
            $request = $this->restClient->get("deliveryOptions/countries/find/all", [
                'json' => [
                    "meta" => $this->greenfieldMeta
                ]
            ]);

            if ($request->getStatusCode() === 200) {
                return json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)["data"];
            }
        } catch (Exception $e) {
            $this->createLogEntry($e->getMessage(), $e->getTrace());
            return false;
        }

        return false;
    }

    public function createLogEntry($message, $trace, $level = 400)
    {
        $this->loggerRepository->create([
            [
                'message' => $message,
                'level' => $level,
                'channel' => 'Administration',
                'context' => $trace
            ]
        ], Context::createDefaultContext());
    }

    private const LIVE_BASE_URI = "https://plc-ecommerce-api.post.at/api/v1/austrianpost/";
    private const TEST_BASE_URI = "https://abn-plc-ecommerce-api.post.at/api/v1/austrianpost/";

    private const LIVE_USER = "austrianPostNodeProd";
    private const TEST_USER = "austrianPostNodeAB";

    private const LIVE_CREDENTIALS = "cOu08Nz7u0Mi2nPzuRDRc1C-uMgAfLug";
    private const TEST_CREDENTIALS = "2Gy8AyLpQBHer4gWz-zab9x2cKodp_Ys";
}
