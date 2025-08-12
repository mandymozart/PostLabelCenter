<?php

namespace PostLabelCenter\Controller\Api;

use Exception;
use PostLabelCenter\Services\GreenFieldService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class ShippingServicesController extends AbstractController
{
    private GreenFieldService $greenFieldService;
    private EntityRepository $plcShippingServicesRepository;

    public function __construct(GreenFieldService $greenFieldService, EntityRepository $plcShippingServicesRepository)
    {
        $this->greenFieldService = $greenFieldService;
        $this->plcShippingServicesRepository = $plcShippingServicesRepository;
    }

    #[Route(
        path: '/api/plc/shipping-services',
        name: 'api.plc.shipping.services',
        methods: ['GET', 'POST']
    )]
    public function getShippingServices(Request $request): JsonResponse
    {
        if (!$request->request->has("salesChannelId") || !$request->request->has("countries") ||
            !Uuid::isValid($request->request->get("salesChannelId"))) {
            return new JsonResponse(["data" => false, "message" => "plc.shippingServices.messages.missingValues"], 200);
        }

        $countries = json_decode($request->request->get("countries"), true, 512, JSON_ERROR_NONE);
        $shippingServices = $this->greenFieldService->getShippingServices($request->request->get("salesChannelId"), $countries);

        if ($shippingServices) {
            return new JsonResponse(["data" => $shippingServices, "message" => 'not empty shipping services'], 200);
        }

        return new JsonResponse(["data" => false, "message" => "plc.shippingServices.messages.noServicesFound"], 200);
    }

    #[Route(
        path: '/api/plc/shipping-services/features',
        name: 'api.plc.shipping.services.features',
        methods: ['GET', 'POST']
    )]
    public function checkShippingFeatureList(Request $request): JsonResponse
    {
        if (!$request->request->has("featureList")) {
            return new JsonResponse(["data" => false, "message" => "plc.shippingServices.messages.missingValues"], 200);
        }

        $featureList = json_decode($request->request->get("featureList"), true);

        $checkFeatures = $this->greenFieldService->checkFeatureList($featureList);

        if (empty($checkFeatures)) {
            return new JsonResponse(["data" => true, "message" => "plc.shippingServices.messages.listCompatible"], 200);
        }

        return new JsonResponse(["data" => false, "message" => "plc.shippingServices.messages.listIncompatible"], 200);

    }

    #[Route(
        path: '/api/plc/shipping-service/upsert',
        name: 'api.plc.shipping.upsert',
        methods: ['POST']
    )]
    public function upsertShippingService(Request $request, Context $context): JsonResponse
    {
        if ((!$request->request->has("featureList")
                || !$request->request->has("shippingProduct")
                || !$request->request->has("displayName")
                || !$request->request->has("countries")
                || !$request->request->has("salesChannelId"))
            || !Uuid::isValid($request->request->get("salesChannelId"))) {
            return new JsonResponse(["data" => false, "message" => "plc.shippingServices.messages.missingValues"], 200);
        }

        if ($request->request->has("id") && !Uuid::isValid($request->request->get("id"))) {
            return new JsonResponse(["data" => false, "message" => "plc.shippingServices.messages.errorSavingService"], 200);
        }

        $countries = [];
        $requestCountries = json_decode($request->request->get("countries"), true);
        if (!empty($requestCountries)) {
            $countries = array_map(static function ($country) {
                return ["id" => $country["id"]];
            }, $requestCountries);
        }

        try {

            $this->plcShippingServicesRepository->upsert([
                [
                    "displayName" => $request->request->get("displayName"),
                    "shippingProduct" => $request->request->get("shippingProduct"),
                    "customsInformation" => $request->request->get("customsInformation"),
                    "featureList" => $request->request->get("featureList"),
                    "countries" => $countries,
                    "salesChannelId" => $request->request->get("salesChannelId"),
                    "id" => $request->request->has("id") ? $request->request->get("id") : Uuid::randomHex()
                ]
            ], $context);
            return new JsonResponse(["data" => true, "message" => "plc.shippingServices.messages.servicesSaved"], 200);
        } catch (Exception $e) {
            return new JsonResponse(["data" => false, "message" => "plc.shippingServices.messages.errorSavingService"], 200);
        }
    }
}
