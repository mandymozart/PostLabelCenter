<?php declare(strict_types=1);

namespace PostLabelCenter\Controller\Storefront;

use PostLabelCenter\Services\GreenFieldService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class GreenFieldController extends StorefrontController
{
    private GreenFieldService $greenFieldService;
    private RouterInterface $router;

    public function __construct(GreenFieldService $greenFieldService,
                                RouterInterface   $router)
    {
        $this->greenFieldService = $greenFieldService;
        $this->router = $router;
    }

    #[Route(
        path: '/plc/return-order',
        name: 'frontend.plc.return.order',
        methods: ['POST'],
        defaults: ["csrf_protected"=>false]
    )]
    public function returnPlcOrder(Request $request, Context $context): Response
    {
        $data = $request->request->all();
        $flashBag = $request->getSession()->getBag("flashes");
        $response = new Response();

        if (!isset($data["salesChannelId"], $data["orderId"]) || !Uuid::isValid($data["salesChannelId"]) || !Uuid::isValid($data["orderId"])) {
            $flashBag->set("danger", [$this->trans("plc.returnOrder.errorMissingData")]);

            return new RedirectResponse($this->router->generate($data["redirectTo"]), 301);
        }

        $lineItems = array_filter($data["lineItems"], static function ($lineItem) {
            return isset($lineItem["lineItemChecked"]) && $lineItem["lineItemChecked"] === "on";
        });

        if (empty($lineItems)) {
            $flashBag->set("danger", [$this->trans("plc.returnOrder.errorNoItemsSelected")]);

            return new RedirectResponse($this->router->generate($data["redirectTo"]), 301);
        }

        $gfResponse = $this->greenFieldService->createFrontendReturn($data["orderId"], $data["salesChannelId"], [
            "lineItems" => $lineItems, "returnNote" => $data["returnNote"], "returnReason" => $data["returnReason"]
        ]);

        if ($gfResponse) {
            $pdfData = $gfResponse["data"][0]["pdfData"];

            $response->setContent(base64_decode($pdfData));
            $response->headers->set('Content-Type', "application/pdf");
            $response->headers->set("Content-Disposition", "attachment;filename=RETURN_LABEL.pdf");

            return $response;
        }

        $flashBag->set("danger", [$this->trans("plc.returnOrder.errorContactShop")]);

        return new RedirectResponse($this->router->generate($data["redirectTo"]), 301);
    }
}
