<?php declare(strict_types=1);

namespace PostLabelCenter\Subscriber;

use PostLabelCenter\Services\GreenFieldService;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiSubscriber implements EventSubscriberInterface
{
    private GreenFieldService $greenFieldService;
    private EntityRepository $orderDeliveryRepository;
    private SystemConfigService $systemConfigService;

    public function __construct(GreenFieldService $greenFieldService, EntityRepository $orderDeliveryRepository, SystemConfigService $systemConfigService)
    {
        $this->greenFieldService = $greenFieldService;
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents()
    {
        return [
            StateMachineTransitionEvent::class => 'stateChangedEvent'
        ];
    }

    public function stateChangedEvent(StateMachineTransitionEvent $event): void
    {
        $disableAutomaticLabel = $this->systemConfigService->get('PostLabelCenter.config.disableAutomaticLabel');
        if ($disableAutomaticLabel !== true && !isset($event->getContext()->getVars()["greenFieldApi"]) && $event->getToPlace()->getTechnicalName() === OrderDeliveryStates::STATE_SHIPPED && !$event->getContext()->getSource() instanceof AdminApiSource) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter("id", $event->getEntityId()));
            $searchOrderDelivery = $this->orderDeliveryRepository->search($criteria, $event->getContext());

            if ($searchOrderDelivery->getTotal() > 0) {
                /** @var OrderDeliveryEntity $delivery */
                $delivery = $searchOrderDelivery->first();
                $this->greenFieldService->createShippingDocuments($delivery->getOrderId(), $delivery->getOrder()->getSalesChannelId());
            }
        }
    }
}
