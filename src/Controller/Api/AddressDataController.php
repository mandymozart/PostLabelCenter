<?php

namespace PostLabelCenter\Controller\Api;

use PHPUnit\Util\Exception;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class AddressDataController extends AbstractController
{
    private EntityRepository $plcAddressDataRepository;

    public function __construct(EntityRepository $plcAddressDataRepository)
    {
        $this->plcAddressDataRepository = $plcAddressDataRepository;
    }

    #[Route(
        path: '/api/plc/address-data/upsert',
        name: 'api.plc.address-data.upsert',
        methods: ['POST']
    )]
    public function upsertAddressData(Request $request, Context $context): JsonResponse
    {
        $payload = $request->request->all();

        foreach (self::REQ_FIELDS as $fieldName) {
            if (!isset($payload[$fieldName])) {
                return new JsonResponse(["data" => false, "message" => "plc.addressData.messages.missingValues"], 200);
            }
        }
        if (!Uuid::isValid($payload["salutationId"]) || !Uuid::isValid($payload["countryId"])
            || !Uuid::isValid($payload["countryId"]) ||
            (!is_null($payload["bankDataId"]) && !Uuid::isValid($payload["bankDataId"]))) {
            return new JsonResponse(["data" => false, "message" => "plc.addressData.messages.invalidValues"], 200);
        }

        if (isset($payload["id"]) && !Uuid::isValid($payload["id"])) {
            return new JsonResponse(["data" => false, "message" => "plc.addressData.messages.invalidValues"], 200);
        }

        if ($payload["defaultAddress"] === true) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter("salesChannelId", $payload["salesChannelId"]));
            $criteria->addFilter(new EqualsFilter("defaultAddress", true));
            if ($payload["addressType"] === "return") {
                $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                        new EqualsFilter("addressType", "return"),
                        new EqualsFilter("addressType", "returnAndShipping"),
                ]));
            } elseif ($payload["addressType"] === "shipping") {
                $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                    new EqualsFilter("addressType", "shipping"),
                    new EqualsFilter("addressType", "returnAndShipping"),
                ]));
            }

            $searchDefaultAddresses = $this->plcAddressDataRepository->search($criteria, $context);

            if ($searchDefaultAddresses->getTotal() > 0) {
                $updateData = array_map(static function ($address) {
                    return [
                        "id" => $address->getId(),
                        "defaultAddress" => false
                    ];
                }, $searchDefaultAddresses->getElements());

                if (!empty($updateData)) {
                    $this->plcAddressDataRepository->update(array_values($updateData), $context);
                }
            }
        }

        try {
            $this->plcAddressDataRepository->upsert([$payload], $context);
            return new JsonResponse(["data" => true, "message" => "plc.addressData.messages.success"], 200);
        } catch (Exception $e) {
            return new JsonResponse(["data" => false, "message" => "plc.addressData.messages.error"], 200);
        }
    }

    private const REQ_FIELDS = [
        "displayName",
        "email",
        "salutationId",
        "firstName",
        "lastName",
        "street",
        "city",
        "zipcode",
        "countryId",
        "phoneNumber",
        "addressType",
        "salesChannelId"
    ];
}