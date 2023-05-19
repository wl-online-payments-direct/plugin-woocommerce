<?php declare(strict_types=1);

namespace MoptWorldline\Core\Checkout\Payment\SalesChannel;

use MoptWorldline\Bootstrap\Form;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\CachedPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OverwriteCachedPaymentMethodRoute extends CachedPaymentMethodRoute
{
    private SalesChannelRepositoryInterface $paymentMethodsRepository;
    private Session $session;
    private EntityRepositoryInterface $customerRepository;

    /**
     * @param AbstractPaymentMethodRoute $decorated
     * @param TagAwareAdapterInterface $cache
     * @param EntityCacheKeyGenerator $generator
     * @param AbstractCacheTracer $tracer
     * @param EventDispatcherInterface $dispatcher
     * @param array $states
     * @param LoggerInterface $logger
     * @param SalesChannelRepositoryInterface $paymentMethodsRepository
     * @param Session $session
     * @param EntityRepositoryInterface $customerRepository
     */
    public function __construct(
        AbstractPaymentMethodRoute      $decorated,
        TagAwareAdapterInterface        $cache,
        EntityCacheKeyGenerator         $generator,
        AbstractCacheTracer             $tracer,
        EventDispatcherInterface        $dispatcher,
        array                           $states,
        LoggerInterface                 $logger,
        SalesChannelRepositoryInterface $paymentMethodsRepository,
        Session                         $session,
        EntityRepositoryInterface       $customerRepository
    )
    {
        parent::__construct($decorated, $cache, $generator, $tracer, $dispatcher, $states, $logger);
        $this->paymentMethodsRepository = $paymentMethodsRepository;
        $this->session = $session;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param Request $request
     * @param SalesChannelContext $context
     * @param Criteria $criteria
     * @return PaymentMethodRouteResponse
     */
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): PaymentMethodRouteResponse
    {
        $key = Form::CUSTOM_FIELD_WORLDLINE_CUSTOMER_SAVED_PAYMENT_CARD_TOKEN;
        $customer = $context->getCustomer();
        if (!is_null($customer)) {
            $fields = $customer->getCustomFields();
            if (!is_null($fields) && array_key_exists($key, $fields) && !empty($fields[$key])) {
                $paymentMethodRoute = new OverwritePaymentMethodRoute(
                    $this->paymentMethodsRepository,
                    $this->session,
                    $this->customerRepository
                );
                return $paymentMethodRoute->load($request, $context, $criteria);
            }
        }
        return parent::load($request, $context, $criteria);
    }
}
