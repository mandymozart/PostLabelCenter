<?php

namespace PostLabelCenter\Controller\Api;

use Exception;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class ReturnReasonController extends AbstractController
{
    private EntityRepository $plcReturnReasonRepository;

    public function __construct(EntityRepository $plcReturnReasonRepository)
    {
        $this->plcReturnReasonRepository = $plcReturnReasonRepository;
    }

    #[Route(
        path: '/api/plc/return-reason/upsert',
        name: 'api.plc.return-reason.upsert',
        methods: ['POST']
    )]
    public function upsertReturnReason(Request $request, Context $context): JsonResponse
    {
        $payload = $request->request->all();

        foreach (self::REQ_FIELDS as $fieldName) {
            if (!isset($payload["returnReason"][$fieldName])) {
                return new JsonResponse(["data" => false, "message" => "plc.returnReasons.messages.missingValues"], 200);
            }
        }

        if (isset($payload["returnReason"]["id"]) && !Uuid::isValid($payload["returnReason"]["id"])) {
            return new JsonResponse(["data" => false, "message" => "plc.returnReasons.messages.invalidValues"], 200);
        }

        $returnReason = [
            "id" => $payload["returnReason"]["id"] ?? Uuid::randomHex(),
            "technicalName" => $payload["returnReason"]["technicalName"],
            "translations" => [
                $payload["translation"]["languageId"] => [
                    "name" => $payload["translation"]["name"]
                ]
            ]
        ];

        if (!isset($payload["returnReason"]["id"])) {
            $returnReason["name"] = $payload["returnReason"]["name"];
        }

        try {
            $this->plcReturnReasonRepository->upsert([$returnReason], $context);
            return new JsonResponse(["data" => true, "message" => "plc.returnReasons.messages.success"], 200);
        } catch (Exception $e) {
            return new JsonResponse(["data" => false, "message" => "plc.returnReasons.messages.error"], 200);
        }
    }

    private const REQ_FIELDS = [
        "name",
        "technicalName",
    ];
}
