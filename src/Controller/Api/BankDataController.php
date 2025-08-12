<?php

namespace PostLabelCenter\Controller\Api;

use PHPUnit\Util\Exception;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class BankDataController extends AbstractController
{
    private EntityRepository $plcBankDataRepository;

    public function __construct(EntityRepository $plcBankDataRepository)
    {
        $this->plcBankDataRepository = $plcBankDataRepository;
    }

    #[Route(
        path: '/api/plc/bank-data/upsert',
        name: 'api.plc.bank-data.upsert',
        methods: ['POST']
    )]
    public function upsertBankData(Request $request, Context $context): JsonResponse
    {
        $payload = $request->request->all();

        foreach (self::REQ_FIELDS as $fieldName) {
            if (!isset($payload[$fieldName])) {
                return new JsonResponse(["data" => false, "message" => "plc.bankData.messages.missingValues"], 200);
            }
        }

        if (isset($payload["id"]) && !Uuid::isValid($payload["id"])) {
            return new JsonResponse(["data" => false, "message" => "plc.bankData.messages.invalidValues"], 200);
        }

        try {
            $this->plcBankDataRepository->upsert([$payload], $context);
            return new JsonResponse(["data" => true, "message" => "plc.bankData.messages.success"], 200);
        } catch (Exception $e) {
            return new JsonResponse(["data" => false, "message" => "plc.bankData.messages.error"], 200);
        }
    }

    private const REQ_FIELDS = [
        "displayName",
        "accountHolder",
        "bic",
        "iban"
    ];
}