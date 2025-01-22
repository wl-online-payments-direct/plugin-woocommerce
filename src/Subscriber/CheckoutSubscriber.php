<?php declare(strict_types=1);

namespace MoptWorldline\Subscriber;

use MoptWorldline\Adapter\WorldlineSDKAdapter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MoptWorldline\Bootstrap\Form;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    /**
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(
        SystemConfigService $systemConfigService
    )
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => ['storefrontRenderEvent', 1]
        ];
    }

    /**
     * @param StorefrontRenderEvent $event
     * @return void
     */
    public function storefrontRenderEvent(StorefrontRenderEvent $event): void
    {
        if ($this->isCheckoutPage($event)) {
            $salesChannelId = $event->getSalesChannelContext()->getSalesChannel()->getId();
            $adapter = new WorldlineSDKAdapter($this->systemConfigService, $salesChannelId);
            if ($adapter->isLiveMode()) {
                $endpoint = $adapter->getPluginConfig(Form::LIVE_ENDPOINT_FIELD);
            } else {
                $endpoint = $adapter->getPluginConfig(Form::SANDBOX_ENDPOINT_FIELD);
            }
            $tokenizerUrl = "$endpoint/hostedtokenization/js/client/tokenizer.min.js";

            $event->setParameter('tokenizerLink', $tokenizerUrl);
        }
    }

    /**
     * @param mixed $event
     * @return bool
     */
    private function isCheckoutPage($event): bool
    {
        $route = $event->getRequest()->attributes->get('_route');
        if (in_array($route, ['frontend.checkout.confirm.page', 'frontend.checkout.cart.page',  'frontend.account.edit-order.page'])) {
            return true;
        }
        return false;
    }
}
